<?php

namespace TelegramBotEssentials\Essence\Web;

use Filament\Contracts\Plugin;
use LogicException;

/**
 * What packages add to the web panel. Holds class names only, so a package
 * can register here from its service provider's register() method, whether
 * or not the app installed Filament.
 */
final class WebPanels
{
    private bool $built = false;

    /** @var list<class-string<Plugin>> */
    private array $manage = [];

    /** @param  class-string<Plugin>  $plugin */
    public function manage(string $plugin): self
    {
        $this->ensureNotBuilt($plugin);
        $this->manage[] = $plugin;

        return $this;
    }

    /** The panel reads the list once, while booting; later additions would be lost. */
    public function markBuilt(): void
    {
        $this->built = true;
    }

    /** @param  class-string<Plugin>  $plugin */
    private function ensureNotBuilt(string $plugin): void
    {
        if ($this->built) {
            throw new LogicException("Register $plugin from your service provider's register() method: the panel is already built.");
        }
    }

    /** @return list<class-string<Plugin>> */
    public function managePlugins(): array
    {
        return array_values(array_unique($this->manage));
    }
}
