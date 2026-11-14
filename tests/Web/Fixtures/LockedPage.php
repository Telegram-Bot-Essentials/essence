<?php

namespace TelegramBotEssentials\Essence\Tests\Web\Fixtures;

use TelegramBotEssentials\Essence\Web\Filament\PanelPage;

class LockedPage extends PanelPage
{
    protected static ?string $ability = 'nobody.has.this';
}
