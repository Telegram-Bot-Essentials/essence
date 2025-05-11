<?php

use Elyar\TelegramBotEssentials\Http\Controllers\TelegramWebhookController;
use Elyar\TelegramBotEssentials\Http\Middleware\TelegramBotAuthentication;
use Illuminate\Support\Facades\Route;

Route::any('/telegram/bot/{unique_id}/webhook', TelegramWebhookController::class)->middleware(TelegramBotAuthentication::class);