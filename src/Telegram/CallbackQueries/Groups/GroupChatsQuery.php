<?php

namespace TelegramBotEssentials\Essence\Telegram\CallbackQueries\Groups;

use Telegram\Bot\Exceptions\TelegramSDKException;
use TelegramBotEssentials\Essence\Models\BotChat;
use TelegramBotEssentials\Essence\Telegram\CallbackQueries\CallbackQuery;
use TelegramBotEssentials\Essence\Telegram\Features\Groups\GroupChatsFeature;

class GroupChatsQuery extends CallbackQuery
{
    protected string $type = GroupChatsFeature::TYPE;

    protected ?string $ability = 'groups.manage';

    /**
     * @throws TelegramSDKException
     */
    public function approve(BotChat $chat): void
    {
        $chat->forceFill(['approved_at' => now()])->save();
        $this->log('approved', $chat);

        GroupChatsFeature::index()->update();
    }

    /**
     * @throws TelegramSDKException
     */
    public function revoke(BotChat $chat): void
    {
        $chat->forceFill(['approved_at' => null])->save();
        $this->log('revoked', $chat);

        GroupChatsFeature::index()->update();
    }

    private function log(string $action, BotChat $chat): void
    {
        tbeLog('essence')->for(wHook()->user())->info('Group chat {chat_id} '.$action, ['chat_id' => $chat->chat_id]);
    }
}
