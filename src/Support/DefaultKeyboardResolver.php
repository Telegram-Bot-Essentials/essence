<?php

namespace TelegramBotEssentials\Essence\Support;

use Illuminate\Contracts\Container\BindingResolutionException;
use Telegram\Bot\Keyboard\Keyboard;
use TelegramBotEssentials\Essence\Contracts\ResolvesKeyboard;
use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Models\Conversation;
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
 * are rows left empty by that.
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
            }
        }

        return $replyMarkup;
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
}
