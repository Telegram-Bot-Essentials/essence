<?php

namespace TelegramBotEssentials\Essence\Console\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tbe:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install all of the TBE resources';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->comment('Publishing TBE Translations...');
        $this->callSilent('vendor:publish', ['--tag' => 'tbe-essence-translations']);

        $this->comment('Publishing TBE Configuration...');
        $this->callSilent('vendor:publish', ['--tag' => 'tbe-essence-config']);

        $this->info('TBE scaffolding installed successfully.');
    }
}
