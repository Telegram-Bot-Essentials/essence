<?php

namespace TelegramBotEssentials\Essence\Web\Filament;

use Filament\Panel;
use Filament\PanelProvider;

class CustomerPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return WebPanelFactory::make($panel, 'customer');
    }
}
