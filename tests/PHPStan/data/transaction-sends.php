<?php

use Illuminate\Support\Facades\DB;

DB::transaction(function () {
    wHook()->api()->sendMessage(['chat_id' => 1, 'text' => 'in transaction']);
    notifyUser(wHook()->user(), 'also in transaction');
});

DB::transaction(fn () => wHook()->api()->editMessageText([]));

// Fine: after the commit, not a Telegram call, and waiting for the commit.
DB::transaction(function () {
    background('later', static fn () => wHook()->api()->getMe());
    collect([])->send();
});
wHook()->api()->sendMessage(['chat_id' => 1, 'text' => 'after the commit']);
