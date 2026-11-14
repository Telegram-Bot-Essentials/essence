<?php

namespace TelegramBotEssentials\Essence\Web\Filament;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Web\LinkCodes;
use TelegramBotEssentials\Essence\Web\TelegramAccount;
use TelegramBotEssentials\Essence\Web\TelegramLink;

/**
 * The signed-in account's Telegram accounts, in any bot's panel: they are
 * the account's, not the bot's. Linking starts here and is confirmed from
 * the Telegram account being added.
 */
class TelegramAccountsPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $slug = 'telegram-accounts';

    protected static bool $shouldRegisterNavigation = false;

    public function getTitle(): string
    {
        return (string) __('tbe-essence::web.telegram_accounts');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedTable::make()]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(TelegramLink::query()->with('telegramUser')->where('account_id', $this->account()->getAuthIdentifier()))
            ->defaultSort('id')
            ->paginated(false)
            ->columns([
                TextColumn::make('telegram_user_peer_id')
                    ->label((string) __('tbe-essence::web.telegram_account'))
                    ->formatStateUsing(fn (TelegramLink $record): string => self::telegramName($record)),
                TextColumn::make('created_at')->label((string) __('tbe-essence::web.linked_at'))->since(),
            ])
            ->recordActions([
                Action::make('unlink')
                    ->label((string) __('tbe-essence::web.unlink'))
                    ->icon(Heroicon::OutlinedLinkSlash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (): bool => $this->mayUnlink())
                    ->action(function (TelegramLink $record): void {
                        // Checked again: the visibility was decided on an older page.
                        if (! $this->mayUnlink()) {
                            return;
                        }
                        $this->account()->unlinkTelegram($record->telegram_user_peer_id);
                        Notification::make()->title((string) __('tbe-essence::web.unlinked'))->success()->send();
                    }),
            ]);
    }

    /** Made when the link dialog opens, so its text and button carry one code. */
    public ?string $linkUrl = null;

    /** @return array<Action> */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('link')
                ->label((string) __('tbe-essence::web.link_another'))
                ->icon(Heroicon::OutlinedLink)
                ->mountUsing(function (): void {
                    $this->linkUrl = $this->newLinkUrl();
                })
                ->modalHeading((string) __('tbe-essence::web.link_another'))
                ->modalDescription((string) __('tbe-essence::web.link_steps'))
                ->modalContent(fn (): Htmlable => new HtmlString($this->linkUrl === null
                    ? '<p>'.e((string) __('tbe-essence::web.link_no_bot')).'</p>'
                    : '<p style="font-size:.875rem;word-break:break-all;">'.e($this->linkUrl).'</p>'))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel((string) __('tbe-essence::web.close'))
                ->extraModalFooterActions(fn (): array => $this->linkUrl === null ? [] : [
                    Action::make('openBot')
                        ->label((string) __('tbe-essence::web.link_open_bot'))
                        ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                        ->url($this->linkUrl, shouldOpenInNewTab: true),
                ]),
        ];
    }

    /** The last link of an account without a password is its only way in. */
    private function mayUnlink(): bool
    {
        $account = $this->account();

        // Typed string, yet null for an account made from Telegram.
        return filled($account->getAuthPassword()) || count($account->linkedPeerIds()) > 1;
    }

    /** A deep link into this panel's bot, or null while its username is unknown. */
    private function newLinkUrl(): ?string
    {
        $bot = Filament::getTenant();
        $username = $bot instanceof Bot ? $bot->username() : null;

        return $username === null
            ? null
            : 'https://t.me/'.$username.'?start='.LinkCodes::PAYLOAD_PREFIX.app(LinkCodes::class)->create($this->account());
    }

    private static function telegramName(TelegramLink $link): string
    {
        $user = $link->telegramUser;
        if ($user === null) {
            return (string) $link->telegram_user_peer_id;
        }

        $name = trim((string) $user->first_name);
        $username = $user->username ? '@'.$user->username : null;

        return implode(' ', array_filter([$name !== '' ? $name : null, $username, '('.$user->peer_id.')']));
    }

    private function account(): TelegramAccount
    {
        $account = Filament::auth()->user();
        abort_unless($account instanceof TelegramAccount, 403);

        return $account;
    }
}
