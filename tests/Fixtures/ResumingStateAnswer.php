<?php

declare(strict_types=1);

namespace TelegramBotEssentials\Essence\Tests\Fixtures;

use TelegramBotEssentials\Essence\Models\MessageMeta;
use TelegramBotEssentials\Essence\Telegram\StateAnswers\StateAnswer;

/**
 * A minimal StateAnswer whose only job is to resume against the MessageMeta
 * named in its params - the shape every real admin flow has when it comes
 * back from waiting on user input.
 */
class ResumingStateAnswer extends StateAnswer
{
    protected string $type = 'RESUMING';

    protected int $perm = 0;

    public function continueFlow(): MessageMeta
    {
        return $this->requireMessageMeta();
    }
}
