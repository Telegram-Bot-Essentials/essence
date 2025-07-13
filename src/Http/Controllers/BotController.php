<?php

namespace Elyar\TelegramBotEssentials\Http\Controllers;

use Elyar\TelegramBotEssentials\Http\Requests\BotRequest;
use Elyar\TelegramBotEssentials\Http\Resources\BotResource;
use Elyar\TelegramBotEssentials\Models\Bot;
use Elyar\TelegramBotEssentials\Models\TelegramUser;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Log;
use Ramsey\Uuid\Uuid;
use Random\RandomException;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

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
            $data = $this->initializeData($request->validated());
            $this->setWebhook($data);

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
     * @throws RandomException
     */
    function initializeData(array $data): array
    {
        $data['secret_token'] = rtrim(strtr(base64_encode(random_bytes(96)), '+/', '-_'), '=');
        $data['unique_id'] = Uuid::uuid4()->toString();
        if (key_exists('activated_until', $data))
            $data['activated_until'] = $data['activated_until'] ? Carbon::parse($data['activated_until'])->format('Y-m-d H:i:s') : null;

        return $data;
    }

    /**
     * @throws TelegramSDKException
     */
    function setWebhook(array $data): void
    {
        $telegram = new Api($data['bot_token']);
        $telegram->setWebhook([
            'url' => config('app.url') . "/api/{$data['unique_id']}/telegram/bot/webhook",
            'drop_pending_updates' => true,
            'secret_token' => $data['secret_token'],
        ]);
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
        if (key_exists('activated_until', $data))
            $data['activated_until'] = $data['activated_until'] ? Carbon::parse($data['activated_until'])->format('Y-m-d H:i:s') : null;
        $bot->update($data);
        if ($request->has('bot_token')) {
            try {
                $telegram = new Api($data['bot_token']);
                $telegram->setWebhook([
                    'url' => config('app.url') . "/api/{$bot->unique_id}/telegram/bot/webhook",
                    'drop_pending_updates' => true,
                    'secret_token' => $bot->secret_token,
                ]);
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
