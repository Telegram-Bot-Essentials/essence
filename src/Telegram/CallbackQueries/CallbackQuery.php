<?php

namespace TelegramBotEssentials\Essence\Telegram\CallbackQueries;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use ReflectionMethod;

abstract class CallbackQuery implements CallbackQueryInterface
{
    protected string $type;
    protected int $perm;
    protected array $params;

    public function getType(): string
    {
        return $this->type;
    }

    public function getPerm(): int
    {
        return $this->perm;
    }

    public function setParams(?array $params): void
    {
        $this->params = $params ?? [];
    }

    public function handle(): void
    {
        $command = strtolower($this->params[0] ?? '');

        $camel = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $command))));
        $method = method_exists($this, $camel) ? $camel : (method_exists($this, $command) ? $command : null);

        if (!$method) {
            wHook()->api()->sendMessage([
                'chat_id' => wHook()->peerId(),
                'text' => "Unavailable",
                'reply_markup' => wHook()->user()->getKeyboard()
            ]);
            return;
        }

        $reflection = new ReflectionMethod($this, $method);
        $parameters = $reflection->getParameters();
        $dependencies = [];

        for($i = 0; $i < $parameters->count(); $i++){
            $param = $parameters[$i];
            $paramData = $this->params[$i] ?? null;
            $paramName = $param->getName();
            $type = $param->getType()?->getName();

            if ($type && class_exists($type) && is_subclass_of($type, Model::class)) {
                $column = $this->bindings[$type] ?? null;
                $value = $this->params[$column ?? $paramName] ?? null;

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
    }

    protected function answer(string $text = ""): void
    {
        wHook()->api()->answerCallbackQuery([
            'callback_query_id' => wHook()->update()->callbackQuery->id,
            'text' => $text
        ]);
    }
}
