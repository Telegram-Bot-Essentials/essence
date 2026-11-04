<?php

namespace Workbench\App\Web;

use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Web\Filament\PanelResource;
use Workbench\App\Web\Pages\ListBotUsers;

/** A stock Filament table: proves the skin and that both tenancies agree. */
class BotUserResource extends PanelResource
{
    protected static ?string $model = BotUser::class;

    protected static ?string $ability = 'web.manage';

    protected static ?string $navigationLabel = 'Users';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $tenantOwnershipRelationshipName = 'bot';

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->sortable(),
            TextColumn::make('telegram_user_peer_id')->label('Peer')->searchable(),
            TextColumn::make('status')->badge(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListBotUsers::route('/')];
    }
}
