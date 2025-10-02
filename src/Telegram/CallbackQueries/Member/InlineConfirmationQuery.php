<?php

namespace TelegramBotEssentials\Essence\Telegram\CallbackQueries\Member;

use TelegramBotEssentials\Essence\Enums\Roles;
use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Essence\Exceptions\TbeLogicException;
use TelegramBotEssentials\Essence\Models\InlineConfirmation;
use TelegramBotEssentials\Essence\Telegram\CallbackQueries\CallbackQuery;
use TelegramBotEssentials\Essence\Telegram\Features\Member\InlineConfirmationFeature;
use Illuminate\Contracts\Container\BindingResolutionException;
use Telegram\Bot\Exceptions\TelegramSDKException;

class InlineConfirmationQuery extends CallbackQuery
{
    protected string $type = 'INLINECONFIRMATION';
    protected int $perm = Roles::MEMBER->value;

    /**
     * @throws BindingResolutionException
     * @throws LogicException
     * @throws TbeLogicException
     */
    public function accept(InlineConfirmation $inlineConfirmation): void
    {
        callbackQueryBus()->routeQuery($inlineConfirmation->callback_data);
        $inlineConfirmation->delete();
    }

    /**
     * @throws BindingResolutionException
     * @throws LogicException
     * @throws TbeLogicException
     */
    public function decline(InlineConfirmation $inlineConfirmation): void
    {
        callbackQueryBus()->routeQuery($inlineConfirmation->back_callback_data);
        $inlineConfirmation->delete();
    }

    /**
     * @throws TelegramSDKException
     */
    public function load(InlineConfirmation $inlineConfirmation): void
    {
        InlineConfirmationFeature::load($inlineConfirmation)->update();
    }
}
