<?php

namespace Workbench\App\Web\Pages;

use Filament\Support\Icons\Heroicon;
use TelegramBotEssentials\Essence\Web\Filament\PanelPage;

class AdminTicket extends PanelPage
{
    use TicketThread;

    protected static ?string $ability = 'web.manage';

    protected string $view = 'workbench::web.ticket';

    protected static ?string $slug = 'ticket';

    protected static ?string $title = 'Ticket #42';

    protected static ?string $navigationLabel = 'Tickets';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedLifebuoy;

    protected function replyAuthor(): string
    {
        return 'Support';
    }
}
