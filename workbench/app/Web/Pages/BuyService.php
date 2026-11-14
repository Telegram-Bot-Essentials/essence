<?php

namespace Workbench\App\Web\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use TelegramBotEssentials\Essence\Web\Filament\PanelPage;

class BuyService extends PanelPage implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'workbench::web.buy-service';

    protected static ?string $slug = 'buy';

    protected static ?string $title = 'Buy service';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    public ?string $paidContext = null;

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): array => [
                'p30' => ['size' => '30 GB', 'period' => '1 month', 'price' => '120,000 T'],
                'p60' => ['size' => '60 GB', 'period' => '1 month', 'price' => '210,000 T'],
                'p120' => ['size' => '120 GB', 'period' => '3 months', 'price' => '380,000 T'],
            ])
            ->columns([
                TextColumn::make('size')->label('Plan')->weight('bold'),
                TextColumn::make('period'),
                TextColumn::make('price')->badge(),
            ])
            ->recordActions([
                Action::make('buy')->button()->icon(Heroicon::OutlinedCreditCard)->action(fn () => $this->pay()),
            ])
            ->paginated(false);
    }

    /** Runs over Livewire's update route, whose URL has no bot in it. */
    public function pay(): void
    {
        $this->paidContext = tenancy()->tenant?->unique_id.' / '.wHook()->bot()->unique_id.' / '.wHook()->user()->telegram_user_peer_id;

        Notification::make()->title('Invoice created')->body($this->paidContext)->success()->send();
    }
}
