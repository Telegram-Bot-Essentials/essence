<?php

declare(strict_types=1);

namespace TelegramBotEssentials\Essence\Exceptions;

/**
 * Thrown when encoded callback data would exceed Telegram's 64-byte limit
 * for a inline button's callback_data.
 *
 * This is a programming error — too much state was pushed into a button —
 * so it fails at keyboard-construction time rather than shipping a button
 * that silently does nothing when tapped. Route the payload through
 * StateData and pass its id instead.
 */
class CallbackDataTooLong extends LogicException {}
