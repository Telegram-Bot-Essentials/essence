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
