<?php

namespace TelegramBotEssentials\Essence\Support;

use Illuminate\Contracts\Container\BindingResolutionException;
use Telegram\Bot\Keyboard\Keyboard;
use TelegramBotEssentials\Essence\Contracts\ResolvesKeyboard;
use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Models\Conversation;
use TelegramBotEssentials\Essence\Telegram\CallbackQueries\Member\GroupKeyQuery;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\Member\CancelProcessKey;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\ReplyKeyInterface;

/**
 * Essence's reply keyboard, in order of precedence:
 *
 *  1. a user mid-flow gets the active state answer's own keyboard, if it
 *     builds one - the handler asked for it, so this holds even with
 *     keyboards turned off;
 *  2. with tbe-essence.keyboard.enabled off, nothing else is shown and any
 *     keyboard already on screen is removed;
 *  3. a user mid-flow otherwise gets a lone Cancel key;
 *  4. otherwise the conversation's current menu, as configured
 *     under tbe-essence.keyboard.menus, falling back to "main".
 *
 * Keys the user may not use, or that are disabled, are left out, and so
 * are rows left empty by that. No menus are configured by default; when
 * there is no menu, or nothing in it is left to show, the keyboard on
 * screen is removed rather than replaced with an empty one, which
 * Telegram would ignore.
 *
 * Group chats never get a reply keyboard: a client shows only the newest
 * reply markup in a group, so one user's keyboard would hide everyone
 * else's, and anyone could press it. There, a flow's keys become inline
 * buttons on the prompt (see GroupKeyQuery), a flow without keys gets an
 * inline Cancel, and outside a flow a markup with no buttons is sent, which
 * leaves every keyboard on screen alone. Never a removal: in a group that
 * would hide other users' keyboards too.
 */
class DefaultKeyboardResolver implements ResolvesKeyboard
{
    public const DEFAULT_MENU = 'main';

    /**
     * @return Keyboard<array-key, mixed>
     *
     * @throws BindingResolutionException
     * @throws LogicException
     */
    public function for(Conversation $conversation): Keyboard
    {
        if (! $conversation->isPrivate()) {
            return $this->forGroup($conversation);
        }

        $user = $conversation->botUser;
        $state = $conversation->state;

        if (! $user instanceof BotUser) {
            return Keyboard::remove();
        }

        if (is_string($state) && $state !== '') {
            $stateKeyboard = stateAnswerBus()->keyboardFor($state, $user);

            if ($stateKeyboard !== null) {
                return $stateKeyboard;
            }
        }

        if (! config()->boolean('tbe-essence.keyboard.enabled', true)) {
            return Keyboard::remove();
        }

        if ($state) {
            return $this->build($user, [[CancelProcessKey::class]]);
        }

        $menu = $conversation->menu !== '' ? $conversation->menu : self::DEFAULT_MENU;

        return $this->build($user, $this->menu($menu) ?? $this->menu(self::DEFAULT_MENU) ?? []);
    }

    /**
     * The rows of a named menu, or null when no such menu is configured.
     *
     * @return array<array-key, mixed>|null
     */
    public function menu(string $name): ?array
    {
        return self::configuredMenus()[$name] ?? null;
    }

    /**
     * Every menu in tbe-essence.keyboard.menus, by name.
     *
     * @return array<string, array<array-key, mixed>>
     */
    public static function configuredMenus(): array
    {
        $configured = config('tbe-essence.keyboard.menus');

        $menus = [];
        foreach (is_array($configured) ? $configured : [] as $name => $rows) {
            if (is_string($name) && is_array($rows)) {
                $menus[$name] = $rows;
            }
        }

        return $menus;
    }

    /**
     * @param  array<array-key, mixed>  $rows  rows of ReplyKey class names
     * @return Keyboard<array-key, mixed>
     *
     * @throws BindingResolutionException
     * @throws LogicException
     */
    protected function build(BotUser $user, array $rows): Keyboard
    {
        $replyMarkup = Keyboard::make()
            ->setResizeKeyboard(config()->boolean('tbe-essence.keyboard.resize', true))
            ->setOneTimeKeyboard(config()->boolean('tbe-essence.keyboard.one_time', true));

        $addedKeys = [];
        $hasRows = false;
        foreach ($rows as $keys) {
            $processedRow = [];
            foreach (is_array($keys) ? $keys : [] as $key) {
                $resolvedKey = $this->resolveReplyKey($key);
                if (! canUseHandler($resolvedKey, $user)) {
                    continue;
                }
                if (! $resolvedKey->isEnabled()) {
                    continue;
                }
                if (in_array($resolvedKey->getText(), $addedKeys, true)) {
                    continue;
                }
                $addedKeys[] = $resolvedKey->getText();
                $processedRow[] = $resolvedKey->getText();
            }
            // A row whose keys are all hidden would leave a gap.
            if ($processedRow !== []) {
                $replyMarkup->row($processedRow);
                $hasRows = true;
            }
        }

        return $hasRows ? $replyMarkup : Keyboard::remove();
    }

    /**
     * @throws BindingResolutionException
     * @throws LogicException
     */
    private function resolveReplyKey(mixed $key): ReplyKeyInterface
    {
        $resolved = is_string($key) ? app()->make($key) : $key;

        if (! $resolved instanceof ReplyKeyInterface) {
            throw new LogicException(sprintf(
                'Menu key "%s" should be an instance of "%s"',
                is_string($key) ? $key : get_debug_type($key),
                ReplyKeyInterface::class,
            ));
        }

        return $resolved;
    }

    /**
     * @return Keyboard<array-key, mixed>
     *
     * @throws BindingResolutionException
     * @throws LogicException
     */
    protected function forGroup(Conversation $conversation): Keyboard
    {
        $state = $conversation->state;

        if (! is_string($state) || $state === '') {
            // A markup with no buttons: Telegram accepts it and leaves the
            // keyboards on screen as they are.
            return Keyboard::make()->setResizeKeyboard(true)->setOneTimeKeyboard(true);
        }

        $inline = Keyboard::make()->inline();
        foreach (self::groupButtons($conversation) as $index => $text) {
            $inline->row([Keyboard::inlineButton([
                'text' => $text,
                'callback_data' => GroupKeyQuery::callbackData($index, $state),
            ])]);
        }

        return $inline;
    }

    /**
     * The buttons a flow offers in a group, in order: the open state
     * answer's own keys, then Cancel unless they already have it. Pressing
     * one replays its text as if the user had typed it.
     *
     * @return list<string>
     *
     * @throws BindingResolutionException
     * @throws LogicException
     */
    public static function groupButtons(Conversation $conversation): array
    {
        $user = $conversation->botUser;
        $state = $conversation->state;

        if (! $user instanceof BotUser || ! is_string($state) || $state === '') {
            return [];
        }

        $rows = stateAnswerBus()->keyboardFor($state, $user)?->toArray()['keyboard'] ?? [];

        $texts = [];
        foreach (is_array($rows) ? $rows : [] as $row) {
            foreach (is_array($row) ? $row : [] as $button) {
                $text = is_array($button) ? ($button['text'] ?? null) : $button;
                if (is_string($text) && $text !== '' && ! in_array($text, $texts, true)) {
                    $texts[] = $text;
                }
            }
        }

        $cancel = app(CancelProcessKey::class)->getText();
        if (! in_array($cancel, $texts, true)) {
            $texts[] = $cancel;
        }

        return $texts;
    }
}
