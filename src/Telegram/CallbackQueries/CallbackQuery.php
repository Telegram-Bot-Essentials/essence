<?php

namespace TelegramBotEssentials\Essence\Telegram\CallbackQueries;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use ReflectionMethod;
use Telegram\Bot\Exceptions\TelegramSDKException;
use TelegramBotEssentials\Essence\Contracts\DeclaresChatScope;
use TelegramBotEssentials\Essence\Contracts\DeclaresPermission;
use TelegramBotEssentials\Essence\Traits\HasChatScope;
use TelegramBotEssentials\Essence\Traits\HasPermission;

abstract class CallbackQuery implements CallbackQueryInterface, DeclaresChatScope, DeclaresPermission
{
    use HasChatScope;
    use HasPermission;

    protected string $type;

    /**
     * In a group, a button on a message sent for one user can only be
     * pressed by that user (MessageOwner). Set true for buttons meant for
     * everyone, such as a poll.
     */
    protected bool $anyoneCanPress = false;

    protected string $method;

    protected array $params;

    public function getType(): string
    {
        return $this->type;
    }

    public function anyoneCanPress(): bool
    {
        return $this->anyoneCanPress;
    }

    public function setMethod(string $method): void
    {
        $this->method = $method;
    }

    public function setParams(?array $params): void
    {
        $this->params = $params ?? [];
    }

    public function handle(): ?bool
    {
        $command = strtolower($this->method);

        $camel = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $command))));
        $method = method_exists($this, $camel) ? $camel : (method_exists($this, $command) ? $command : null);

        if (! $method) {
            debugMessage("Method {$this->method} is not available in {$this->getType()} callback query");
            $this->answer('Method is not available');

            return false;
        }

        $reflection = new ReflectionMethod($this, $method);
        $parameters = $reflection->getParameters();
        $dependencies = [];

        for ($i = 0; $i < count($parameters); $i++) {
            $param = $parameters[$i];
            $paramData = $this->params[$i] ?? null;
            $paramName = $param->getName();
            $type = $param->getType()?->getName();

            if ($type && class_exists($type) && is_subclass_of($type, Model::class)) {
                $column = $this->bindings[$type] ?? null;
                $value = $this->params[$i] ?? null;

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

            throw new \Exception("Unable to resolve parameter {$paramName} for method {$method}");
        }

        $this->{$method}(...$dependencies);

        return true;
    }

    protected function answer(?string $text = null): void
    {
        try {
            wHook()->api()->answerCallbackQuery(array_filter([
                'callback_query_id' => wHook()->update()->callbackQuery->id,
                'text' => $text,
            ]));
        } catch (TelegramSDKException) {
        }
    }

    public function isEnabled(): bool
    {
        return true;
    }
}
