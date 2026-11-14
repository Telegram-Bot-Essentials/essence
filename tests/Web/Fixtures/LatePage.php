<?php

namespace TelegramBotEssentials\Essence\Tests\Web\Fixtures;

use TelegramBotEssentials\Essence\Web\Filament\PanelPage;

class LatePage extends PanelPage
{
    protected string $view = 'workbench::web.late';

    protected static ?string $slug = 'late';
}
