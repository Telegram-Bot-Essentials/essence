<?php

namespace Workbench\App\Web\Pages;

use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;

/** The ticket thread: the same on both panels, so it cannot drift apart. */
trait TicketThread
{
    public ?array $data = [];

    public array $messages = [];

    public function mount(): void
    {
        $this->messages = [
            ['author' => 'Sara', 'staff' => false, 'text' => 'My service stopped working this morning.', 'time' => '09:12'],
            ['author' => 'Support', 'staff' => true, 'text' => 'Please try the new link. It should connect now.', 'time' => '09:30'],
        ];
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([Textarea::make('reply')->label('Your reply')->required()->rows(3)])
            ->statePath('data');
    }

    public function send(): void
    {
        $state = $this->form->getState();
        $this->messages[] = ['author' => $this->replyAuthor(), 'staff' => $this->replyAuthor() === 'Support', 'text' => $state['reply'], 'time' => now()->format('H:i')];
        $this->form->fill();

        Notification::make()->title('Reply sent')->success()->send();
    }

    abstract protected function replyAuthor(): string;
}
