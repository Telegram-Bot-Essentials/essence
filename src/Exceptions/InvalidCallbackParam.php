<?php

declare(strict_types=1);

namespace TelegramBotEssentials\Essence\Exceptions;

/**
 * Thrown when a callback param cannot be represented as a string.
 *
 * Scalars, null and Stringable objects all encode. Arrays and other
 * objects do not, and silently dropping them used to shift every later
 * param one position left.
 */
class InvalidCallbackParam extends LogicException {}
