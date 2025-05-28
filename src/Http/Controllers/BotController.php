<?php

namespace Elyar\TelegramBotEssentials\Http\Controllers;

use Elyar\TelegramBotEssentials\Http\Requests\BotRequest;
use Elyar\TelegramBotEssentials\Http\Resources\BotResource;
use Elyar\TelegramBotEssentials\Models\Bot;
use Elyar\TelegramBotEssentials\Models\TelegramUser;
use Elyar\TelegramBotEssentials\Traits\HttpResponses;
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
    use HttpResponses;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $bots = Bot::all();
        $result = BotResource::collection($bots);
        return $this->success($result);
    }

    /**
     * Store a newly created resource in storage.
     * @param BotRequest $request
     * @return JsonResponse
     * @throws RandomException
     */
    public function store(BotRequest $request)
    {
        $data = $request->validated();
        TelegramUser::firstOrCreate(['peer_id' => $data['bot_owner_peer_id']]);
        $secretToken = rtrim(strtr(base64_encode(random_bytes(96)), '+/', '-_'), '=');
        $uuid = Uuid::uuid4()->toString();
        $data['secret_token'] = $secretToken;
        $data['unique_id'] = $uuid;
        $data['activated_until'] = $data['activated_until'] ? Carbon::parse($data['activated_until'])->format('Y-m-d H:i:s') : null;
        $bot = Bot::create($data);
        $data = new BotResource($bot);
        try {
            $telegram = new Api($data['bot_token']);
            $telegram->setWebhook([
                'url' => config('app.url') . "/api/{$uuid}/telegram/bot/webhook",
                'drop_pending_updates' => true,
                'secret_token' => $secretToken,
            ]);
        } catch (TelegramSDKException $e) {
            Log::error($e->getMessage());
        }
        return $this->success($data, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $bot = Bot::where('unique_id', $id)->firstOrFail();
        $data = new BotResource($bot);
        return $this->success($data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(BotRequest $request, string $id)
    {
        $bot = Bot::where('unique_id', $id)->firstOrFail();
        $data = $request->validated();
        if(key_exists('activated_until', $data))
            $data['activated_until'] = $data['activated_until'] ? Carbon::parse($data['activated_until'])->format('Y-m-d H:i:s') : null;
        $bot->update($data);
        if($request->has('bot_token')){
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
        return $this->success($data);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $bot = Bot::where('unique_id', $id)->firstOrFail();
        $bot->delete();
        return $this->success(code: 204);
    }
}
