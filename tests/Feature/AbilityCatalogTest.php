<?php

declare(strict_types=1);

use Illuminate\Support\Facades\App;
use TelegramBotEssentials\Essence\Forms\Form;
use TelegramBotEssentials\Essence\Support\AbilityCatalog;
use TelegramBotEssentials\Essence\Telegram\Commands\Command;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\ReplyKey;
use TelegramBotEssentials\Essence\Telegram\TelegramResponse;

/*
 * The catalog is read from the handlers themselves: an ability named on a
 * reply key, command, form, ... is listed without being written down a
 * second time.
 */

class CatalogReportsCommand extends Command
{
    protected string $name = 'catalog_reports';

    protected ?string $ability = 'reports.view';

    public function handle(): ?bool
    {
        return true;
    }
}

class CatalogPublicCommand extends Command
{
    protected string $name = 'catalog_public';

    public function handle(): ?bool
    {
        return true;
    }
}

class CatalogShopKey extends ReplyKey
{
    protected ?string $ability = 'shop.panels.manage';

    protected function text(): string
    {
        return 'Panels';
    }

    public function handle(): void {}
}

class CatalogShopForm extends Form
{
    protected ?string $ability = 'shop.panels.manage';

    protected string $type = 'CATALOG_SHOP';

    public function steps(): array
    {
        return [];
    }

    public function onComplete(array $answers, array $ctx): TelegramResponse
    {
        return TelegramResponse::make();
    }
}

beforeEach(function () {
    commandBus()->addCommands([CatalogReportsCommand::class, CatalogPublicCommand::class]);
    replyKeyBus()->addReplyKey(CatalogShopKey::class);
    formRegistry()->addForm(CatalogShopForm::class);
});

it('lists every ability a registered handler asks for, once, sorted', function () {
    expect(abilities()->all())->toBe(['reports.view', 'shop.panels.manage']);
});

it('lists an ability that only code checks once it is declared', function () {
    abilities()->declare('settings.channel_lock.bypass');

    expect(abilities()->all())->toContain('settings.channel_lock.bypass')
        ->and(abilities()->has('settings.channel_lock.bypass'))->toBeTrue()
        ->and(abilities()->has('nothing.asks.for.this'))->toBeFalse();
});

it('sees a handler registered after it was first asked', function () {
    abilities()->all();
    commandBus()->addCommand(new class extends Command
    {
        protected string $name = 'catalog_late';

        protected ?string $ability = 'late.manage';

        public function handle(): ?bool
        {
            return true;
        }
    });

    expect(abilities()->all())->toContain('late.manage');
});

it('groups abilities by their first segment', function () {
    expect(abilities()->grouped())->toBe([
        'reports' => ['reports.view'],
        'shop' => ['shop.panels.manage'],
    ]);
});

it('labels an ability from the app abilities file by its full dotted name', function () {
    app('translator')->getLoader()->addPath(__DIR__.'/../Fixtures/lang');

    expect(abilities()->label('shop.panels.manage'))->toBe('Manage panels');
});

it('labels an ability from a package abilities file', function () {
    app('translator')->addNamespace('tbe-reports', __DIR__.'/../Fixtures/lang-package');

    expect(abilities()->label('reports.export'))->toBe('Export reports');
});

it('lets the app abilities file override a package label', function () {
    app('translator')->getLoader()->addPath(__DIR__.'/../Fixtures/lang');
    app('translator')->addNamespace('tbe-reports', __DIR__.'/../Fixtures/lang-package');

    expect(abilities()->label('reports.view'))->toBe('See reports');
});

it('prefers the label given to declare()', function () {
    app('translator')->getLoader()->addPath(__DIR__.'/../Fixtures/lang');
    abilities()->declare('reports.view', fn () => 'From declare');

    expect(abilities()->label('reports.view'))->toBe('From declare');
});

it('follows the locale', function () {
    app('translator')->getLoader()->addPath(__DIR__.'/../Fixtures/lang');

    App::setLocale('fa');

    expect(abilities()->label('reports.view'))->toBe('دیدن گزارش‌ها');
});

it('falls back to the name when nothing labels it', function () {
    expect(abilities()->label('shop.panels.manage'))->toBe('shop.panels.manage');
});

it('is one catalog for the whole process', function () {
    expect(abilities())->toBe(app(AbilityCatalog::class));
});
