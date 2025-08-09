<?php

namespace Elyar\TelegramBotEssentials\Telegram\StateAnswers\Admin;

use Brick\Math\BigDecimal;
use Elyar\TelegramBotEssentials\Enums\AllowableFields;
use Elyar\TelegramBotEssentials\Enums\Roles;
use Elyar\TelegramBotEssentials\Exceptions\InvalidPageNumber;
use Elyar\TelegramBotEssentials\Exceptions\LogicException;
use Elyar\TelegramBotEssentials\Exceptions\TbeLogicException;
use Elyar\TelegramBotEssentials\Models\BotUser;
use Elyar\TelegramBotEssentials\Models\MessageMeta;
use Elyar\TelegramBotEssentials\Services\TelegramPaginator;
use Elyar\TelegramBotEssentials\Telegram\Features\Admin\BotUsersFeature;
use Elyar\TelegramBotEssentials\Telegram\StateAnswers\StateAnswer;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Validator;
use Telegram\Bot\Exceptions\TelegramSDKException;

class BotUsersAnswer extends StateAnswer
{
    protected string $type = 'BOTUSERS';
    protected int $perm = Roles::ADMIN->value;
    protected array $allowedFields = [
        AllowableFields::TEXT->value
    ];

    /**
     * @param string $method
     * @throws BindingResolutionException
     * @throws LogicException
     * @throws TelegramSDKException|InvalidPageNumber
     */
    public function handle(string $method): void
    {
        switch (strtolower($method)) {
            case "set_start_page":
                $this->setStartPage();
                break;
            case "balance":
                $this->balance();
                break;
        }
    }

    /**
     * @throws TelegramSDKException
     * @throws LogicException
     * @throws BindingResolutionException
     * @throws InvalidPageNumber
     */
    public function setStartPage(): void
    {
        $messageMeta = MessageMeta::findOrFail($this->params['message_meta_id']);
        $page = wHook()->update()->message->text;
        $lastPage = BotUser::paginate(perPage: 10)->lastPage();

        TelegramPaginator::validatePageInput($page, $lastPage);

        $data = BotUsersFeature::start(intval($page));

        wHook()->user()->changeState();
        wHook()->api()->sendMessage([
            'chat_id' => wHook()->user()->telegramUser->peer_id,
            'text' => "Page $page loaded",
            'reply_markup' => wHook()->user()->getKeyboard(),
        ]);

        $messageMeta->updateAndContinueAction($data);
    }

    /**
     * @throws TelegramSDKException
     * @throws BindingResolutionException
     * @throws LogicException
     */
    private function balance(): void
    {
        $botUser = BotUser::findOrFail($this->params['bot_user_id']);
        $type = $this->params['type'];
        $messageMeta = MessageMeta::findOrFail($this->params['message_meta_id']);
        $lastPage = $this->params['last_page'];

        $amount = wHook()->update()->message->text;

        if ($type == 'add') {
            Validator::validate(
                ['amount' => $amount],
                ['amount' => "required|numeric|min:-100000000|max:100000000"]
            );
            $amount = BigDecimal::of($amount);
            if(BigDecimal::of($botUser->balance)->plus($amount)->compareTo(BigDecimal::zero()) < 0){
                throw new TbeLogicException('User balance cannot be less than 0');
            }
            if ($amount->compareTo(BigDecimal::zero()) > 0) {
                wHook()->runForUser($botUser, function () use ($amount) {
                    gateways()->wallet()->addAmount($amount);
                });
            }else {
                wHook()->runForUser($botUser, function () use ($amount) {
                    gateways()->wallet()->takeAmount($amount);
                });
            }
        } elseif ($type == 'set') {
            Validator::validate(
                ['amount' => $amount],
                ['amount' => "required|numeric|min:0|max:100000000"]
            );
            $amount = BigDecimal::of($amount);
            wHook()->runForUser($botUser, function () use ($amount) {
                gateways()->wallet()->setAmount($amount);
            });
        }

        $data = BotUsersFeature::show($botUser, $lastPage);

        wHook()->user()->changeState();
        wHook()->api()->sendMessage([
            'chat_id' => wHook()->user()->telegramUser->peer_id,
            'text' => "User " . $botUser->telegramUser->full_name . " balance updated to " . currency()->priceFormat($botUser->balance),
            'reply_markup' => wHook()->user()->getKeyboard(),
        ]);

        $messageMeta->updateAndContinueAction($data);
    }

    /**
     * @throws TelegramSDKException
     */
    function cancel(): void
    {
        $messageMeta = MessageMeta::find($this->params['message_meta_id']);
        if ($messageMeta) {
            $messageMeta->continueAction();
        }
    }
}
