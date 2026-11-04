<?php

namespace Workbench\App\Web\Pages;

use Filament\Resources\Pages\ListRecords;
use TelegramBotEssentials\Essence\Web\Filament\Concerns\GuardsResourcePage;
use Workbench\App\Web\BotUserResource;

class ListBotUsers extends ListRecords
{
    use GuardsResourcePage;

    protected static string $resource = BotUserResource::class;
}
