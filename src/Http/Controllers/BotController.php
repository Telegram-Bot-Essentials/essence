<?php

namespace Elyar\TelegramBotEssentials\Http\Controllers;

use Elyar\TelegramBotEssentials\Http\Requests\BotRequest;
use Elyar\TelegramBotEssentials\Http\Resources\BotResource;
use Elyar\TelegramBotEssentials\Models\Bot;
use Elyar\TelegramBotEssentials\Traits\HttpResponses;
use Illuminate\Routing\Controller;

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
     */
    public function store(BotRequest $request)
    {
        $data = $request->validated();
        $data['secret_token'] = 'xxx';
        $data['unique_id'] = 'xxx';
        $bot = Bot::create($data);
        $data = new BotResource($bot);
        return $this->success($data, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $bot = Bot::findOrFail($id);
        $data = new BotResource($bot);
        return $this->success($data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(BotRequest $request, string $id)
    {
        $bot = Bot::findOrFail($id);
        $bot->update($request->validated());
        $data = new BotResource($bot);
        return $this->success($data);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $bot = Bot::findOrFail($id);
        $bot->delete();
        return $this->success(code: 204);
    }
}
