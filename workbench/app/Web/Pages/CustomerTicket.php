<?php

namespace Workbench\App\Web\Pages;

use Filament\Support\Icons\Heroicon;
use TelegramBotEssentials\Essence\Web\Filament\PanelPage;

class CustomerTicket extends PanelPage
{
    use TicketThread;

    protected string $view = 'workbench::web.ticket';

    protected static ?string $slug = 'ticket';

    protected static ?string $title = 'Service stopped working';

    protected static ?string $navigationLabel = 'My ticket';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedLifebuoy;

    protected function replyAuthor(): string
    {
        return 'Sara';
    }
}
