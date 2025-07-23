<?php

namespace Elyar\TelegramBotEssentials\Telegram\CallbackQueries\Member;

use Elyar\TelegramBotEssentials\Enums\Roles;
use Elyar\TelegramBotEssentials\Exceptions\LogicException;
use Elyar\TelegramBotEssentials\Exceptions\TbeLogicException;
use Elyar\TelegramBotEssentials\Models\InlineConfirmation;
use Elyar\TelegramBotEssentials\Telegram\CallbackQueries\CallbackQuery;
use Elyar\TelegramBotEssentials\Telegram\Feature\Member\InlineConfirmationFeature;
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
     * @throws TelegramSDKException
     */
    public function handle(array $params): void
    {
        $this->params = $params;
        switch (strtolower($params[0])) {
            case "load":
                $this->load();
                break;
            case "accept":
                $this->accept();
                break;
            case 'decline':
                $this->decline();
                break;
        }
    }

    /**
     * @throws BindingResolutionException
     * @throws LogicException
     * @throws TbeLogicException
     */
    private function accept(): void
    {
        $inlineConfirmation = InlineConfirmation::findOrFail($this->params[1]);
        callbackQueryBus()->routeQuery($inlineConfirmation->callback_data);
        $inlineConfirmation->delete();
    }

    /**
     * @throws BindingResolutionException
     * @throws LogicException
     * @throws TbeLogicException
     */
    private function decline(): void
    {
        $inlineConfirmation = InlineConfirmation::findOrFail($this->params[1]);
        callbackQueryBus()->routeQuery($inlineConfirmation->back_callback_data);
        $inlineConfirmation->delete();
    }

    /**
     * @throws TelegramSDKException
     */
    private function load(): void
    {
        $inlineConfirmation = InlineConfirmation::findOrFail($this->params[1]);
        InlineConfirmationFeature::load($inlineConfirmation)->update();
    }
}
