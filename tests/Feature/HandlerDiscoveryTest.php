<?php

use TelegramBotEssentials\Essence\Telegram\CallbackQueries\CallbackQuery;
use TelegramBotEssentials\Essence\Telegram\CallbackQueries\Member\GroupKeyQuery;

it('finds handlers in any sub-folder, under their real class names', function () {
    $classes = handlerClassesIn(dirname(__DIR__, 2).'/src/Telegram/CallbackQueries', CallbackQuery::class);

    expect($classes)->toContain(GroupKeyQuery::class)
        ->and($classes)->not->toContain(CallbackQuery::class);
});

it('finds nothing in a folder that does not exist', function () {
    expect(handlerClassesIn('/nowhere', CallbackQuery::class))->toBe([]);
});
