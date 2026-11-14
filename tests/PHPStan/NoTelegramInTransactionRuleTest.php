<?php

declare(strict_types=1);

namespace TelegramBotEssentials\Essence\Tests\PHPStan;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use TelegramBotEssentials\Essence\PHPStan\NoTelegramInTransactionRule;

/**
 * @extends RuleTestCase<NoTelegramInTransactionRule>
 */
class NoTelegramInTransactionRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoTelegramInTransactionRule;
    }

    public function test_it_flags_telegram_calls_inside_a_transaction_only(): void
    {
        $message = 'Telegram is called inside DB::transaction(): a failed send would roll back the work. Send after the commit, or use background().';

        $this->analyse([__DIR__.'/data/transaction-sends.php'], [
            [$message, 6],
            [$message, 7],
            [$message, 10],
        ]);
    }
}
