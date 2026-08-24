<?php

declare(strict_types=1);

namespace TelegramBotEssentials\Essence\Telegram\Commands;

use Exception;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use ReflectionMethod;
use TelegramBotEssentials\Essence\Events\BotCommandHandled;
use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Essence\Exceptions\TbeLogicException;
use TelegramBotEssentials\Essence\Support\WebhookContext;
use TelegramBotEssentials\Essence\Traits\CanResolveCommand;

class CommandBus
{
    use CanResolveCommand;

    /** @var array<string, CommandInterface> Keyed by command name (without leading slash). */
    private array $commands = [];

    /** @var array<string, CommandInterface> Keyed by alias (without leading slash). */
    private array $aliases = [];

    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * @throws BindingResolutionException
     * @throws LogicException
     */
    public function addCommands(iterable $commands): self
    {
        foreach ($commands as $command) {
            $this->addCommand($command);
        }

        return $this;
    }

    /**
     * Registering the same command name twice (e.g. TelegramWebhookController
     * re-scanning app/Telegram/Commands/* on every request against a
     * singleton bus) replaces the prior registration rather than conflicting
     * with it: this command's own previous aliases are freed first, so only
     * a genuinely different command claiming one of them still conflicts.
     *
     * @throws LogicException
     * @throws BindingResolutionException
     */
    public function addCommand(CommandInterface|string $command): void
    {
        $command = $this->resolveCommand($command);
        $name = $command->getName();

        if (isset($this->commands[$name])) {
            foreach ($this->commands[$name]->getAliases() as $alias) {
                unset($this->aliases[$alias]);
            }
        }

        $this->commands[$name] = $command;

        foreach ($command->getAliases() as $alias) {
            $this->checkForConflicts($command, $alias);
            $this->aliases[$alias] = $command;
        }
    }

    public function removeCommands(array $names): self
    {
        foreach ($names as $name) {
            $this->removeCommand($name);
        }

        return $this;
    }

    public function removeCommand(string $name): self
    {
        unset($this->commands[$name]);

        return $this;
    }

    /**
     * Trigger a registered command directly — as if the user had called it themselves.
     * Used both for real incoming /command messages (via processCommands()) and for
     * routing into a command programmatically from another bus (callback query, reply
     * key, state answer, ...). Does not cancel any pending state/process on its own;
     * the caller is responsible for that if it applies to their flow.
     *
     * @throws BindingResolutionException
     * @throws LogicException
     * @throws TbeLogicException
     */
    public function route(string $name, array $params = []): bool
    {
        $key = $this->commands[$name] ?? $this->aliases[$name] ?? null;
        if (! $key) {
            tbeLog('essence')->warning('Command "'.$name.'" is not registered');
            throw new TbeLogicException(__('tbe::general.command.notFound'));
        }

        // Fresh instance to handle with: the registered one is a long-lived
        // template shared by every request on this worker.
        $command = $this->resolveCommand($key::class);

        return $this->handler($command, $name, $params);
    }

    /**
     * @throws BindingResolutionException
     */
    protected function handler(CommandInterface $command, string $name, array $params): bool
    {
        if (! $command->isEnabled()) {
            return false;
        }
        if (! hasAccess($command->getPerm())) {
            return false;
        }

        $command->setParams($params);
        $result = $this->invoke($command, $params);

        botEventBus()->fire(new BotCommandHandled(WebhookContext::capture(), '/'.$name));

        return $result ?? true;
    }

    private function invoke(CommandInterface $command, array $params): ?bool
    {
        $reflection = new ReflectionMethod($command, 'handle');
        $parameters = $reflection->getParameters();
        $dependencies = [];

        for ($i = 0; $i < count($parameters); $i++) {
            $param = $parameters[$i];
            $paramData = $params[$i] ?? null;
            $paramName = $param->getName();
            $type = $param->getType()?->getName();

            if ($type && class_exists($type) && is_subclass_of($type, Model::class)) {
                $column = ($command instanceof Command) ? ($command->getBindings()[$type] ?? null) : null;
                $value = $params[$i] ?? null;

                $dependencies[] = $type::where($column ?? 'id', $value)->firstOrFail();

                continue;
            }

            if ($type && class_exists($type)) {
                $dependencies[] = Container::getInstance()->make($type);

                continue;
            }

            if (isset($paramData)) {
                $dependencies[] = $paramData;

                continue;
            }

            if ($param->isDefaultValueAvailable()) {
                $dependencies[] = $param->getDefaultValue();

                continue;
            }

            throw new Exception("Unable to resolve parameter {$paramName} for command \"{$command->getName()}\"");
        }

        return $command->handle(...$dependencies);
    }

    /**
     * @throws BindingResolutionException
     * @throws LogicException
     * @throws TbeLogicException
     */
    public function processCommands(): bool
    {
        $update = wHook()->update();

        if (! $update->isType('message')) {
            return false;
        }
        $text = $update->getMessage()->text ?? '';
        if (! str_starts_with($text, '/')) {
            return false;
        }

        [$name, $params] = $this->parseCommandText($text);

        return $this->route($name, $params);
    }

    private function parseCommandText(string $text): array
    {
        $parts = preg_split('/\s+/', trim($text));
        $name = strtolower(explode('@', ltrim(array_shift($parts), '/'))[0]);

        return [$name, $parts];
    }

    /**
     * @throws LogicException
     */
    private function checkForConflicts(CommandInterface $command, string $alias): void
    {
        if (isset($this->commands[$alias])) {
            throw new LogicException(
                sprintf('Alias "%s" conflicts with command name of "%s"', $alias, $command::class)
            );
        }

        if (isset($this->aliases[$alias])) {
            throw new LogicException(
                sprintf('Alias "%s" conflicts with another command\'s alias: "%s"', $alias, $command::class)
            );
        }
    }
}
