<?php

use Elyar\TelegramBotEssentials\Http\Controllers\BotController;
use Elyar\TelegramBotEssentials\Http\Controllers\TelegramWebhookController;
use Elyar\TelegramBotEssentials\Http\Middleware\TelegramBotAuthentication;
use Illuminate\Support\Facades\Route;

Route::get('/telegram/bot/{unique_id}/webhook', function (){
    return response('OK',200);
});

Route::post('/telegram/bot/{unique_id}/webhook', TelegramWebhookController::class)->middleware(TelegramBotAuthentication::class);

Route::apiResource('bot', BotController::class);