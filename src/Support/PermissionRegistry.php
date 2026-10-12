<?php

namespace TelegramBotEssentials\Essence\Support;

use Closure;
use TelegramBotEssentials\Essence\Exceptions\TbeException;

/**
 * Every permission name the installed packages know about. Packages
 * register theirs from their service provider's boot(); a role system
 * (tbe-access) reads this to offer them to the bot owner.
 *
 * Pure in-memory data: essence's own DefaultAuthorizer never consults it,
 * so declaring a permission costs nothing for a bot without roles.
 */
class PermissionRegistry
{
    /** @var array<string, Permission> */
    private array $permissions = [];

    /**
     * @param  Closure(): string|string  $label  a translation key or a closure returning the label
     *
     * @throws TbeException
     */
    public function register(string $name, Closure|string $label, string $group = 'general'): void
    {
        if (isset($this->permissions[$name])) {
            throw new TbeException("Permission \"{$name}\" is already registered");
        }

        $this->permissions[$name] = new Permission($name, $label, $group);
    }

    public function has(string $name): bool
    {
        return isset($this->permissions[$name]);
    }

    public function get(string $name): ?Permission
    {
        return $this->permissions[$name] ?? null;
    }

    /** @return array<string, Permission> */
    public function all(): array
    {
        return $this->permissions;
    }

    /** @return array<string, array<string, Permission>> */
    public function grouped(): array
    {
        $groups = [];
        foreach ($this->permissions as $name => $permission) {
            $groups[$permission->group][$name] = $permission;
        }

        return $groups;
    }
}
