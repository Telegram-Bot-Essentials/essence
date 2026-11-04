<?php

namespace TelegramBotEssentials\Essence\Web\Filament;

use Filament\Panel;
use Filament\PanelProvider;

class ManagePanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return WebPanelFactory::make($panel, 'manage')->default();
    }
}
