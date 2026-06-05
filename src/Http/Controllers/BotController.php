<?php

namespace TelegramBotEssentials\Essence\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Log;
use Ramsey\Uuid\Uuid;
use Random\RandomException;
use Telegram\Bot\Exceptions\TelegramSDKException;
use TelegramBotEssentials\Essence\Http\Requests\BotRequest;
use TelegramBotEssentials\Essence\Http\Resources\BotResource;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\TelegramUser;

class BotController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $bots = Bot::all();
        $result = BotResource::collection($bots);
        return apiResponse()->success($result);
    }

    /**
     * Store a newly created resource in storage.
     * @param BotRequest $request
     * @return JsonResponse
     */
    public function store(BotRequest $request)
    {
        try {
            [$data, $secretTokenPlain] = $this->initializeData($request->validated());
            $this->setWebhook($data['bot_token'], $data['unique_id'], $secretTokenPlain);

            TelegramUser::firstOrCreate(['peer_id' => $data['bot_owner_peer_id']]);
            $bot = Bot::create($data);

            $botResource = new BotResource($bot);
            return apiResponse()->success($botResource, 201);
        } catch (TelegramSDKException $e) {
            Log::error($e->getMessage(), $e->getTrace());
            return apiResponse()->error('failed to set webhook', 503);
        } catch (Exception $e) {
            Log::error($e->getMessage(), $e->getTrace());
            return apiResponse()->error('failed to create bot', 500);
        }
    }

    /**
     * @return array{0: array<string, mixed>, 1: string}
     * @throws RandomException
     */
    private function initializeData(array $data): array
    {
        $secretToken = $this->generateSecretToken();
        $data['secret_token'] = Hash::make($secretToken);
        $data['unique_id'] = Uuid::uuid4()->toString();

        if (key_exists('activated_until', $data)) {
            $data['activated_until'] = $data['activated_until']
                ? Carbon::parse($data['activated_until'])->format('Y-m-d H:i:s')
                : null;
        }

        return [$data, $secretToken];
    }

    /**
     * @throws TelegramSDKException
     */
    private function setWebhook(string $botToken, string $uniqueId, string $secretToken): void
    {
        $telegram = telegramApi($botToken);
        $telegram->setWebhook([
            'url' => config('app.url') . "/api/{$uniqueId}/telegram/bot/webhook",
            'drop_pending_updates' => true,
            'secret_token' => $secretToken,
        ]);
    }

    /**
     * @throws RandomException
     */
    private function generateSecretToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(96)), '+/', '-_'), '=');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $bot = Bot::where('unique_id', $id)->firstOrFail();
        $data = new BotResource($bot);
        return apiResponse()->success($data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(BotRequest $request, string $id)
    {
        $bot = Bot::where('unique_id', $id)->firstOrFail();
        $data = $request->validated();
        if (key_exists('activated_until', $data)) {
            $data['activated_until'] = $data['activated_until']
                ? Carbon::parse($data['activated_until'])->format('Y-m-d H:i:s')
                : null;
        }

        $shouldRefreshSecret = $request->has('bot_token');
        $secretTokenPlain = null;

        if ($shouldRefreshSecret) {
            try {
                $secretTokenPlain = $this->generateSecretToken();
            } catch (RandomException $e) {
                Log::error($e->getMessage(), $e->getTrace());
                return apiResponse()->error('failed to refresh bot secret', 500);
            }

            $data['secret_token'] = Hash::make($secretTokenPlain);
        }

        $bot->update($data);

        if ($shouldRefreshSecret && $secretTokenPlain) {
            try {
                $this->setWebhook($bot->bot_token, $bot->unique_id, $secretTokenPlain);
            } catch (TelegramSDKException $e) {
                Log::error($e->getMessage());
            }
        }
        $data = new BotResource($bot);
        return apiResponse()->success($data);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $bot = Bot::where('unique_id', $id)->firstOrFail();
        $bot->delete();
        return apiResponse()->success(code: 204);
    }
}
