<?php

namespace TelegramBotEssentials\Essence\Telegram\StateAnswers;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use ReflectionMethod;
use TelegramBotEssentials\Essence\Support\ResolvesParameters;
use TelegramBotEssentials\Essence\Enums\AllowableFields;
use TelegramBotEssentials\Essence\Exceptions\TbeLogicException;
use TelegramBotEssentials\Essence\Models\MessageMeta;
use TelegramBotEssentials\Essence\Models\StateData;

abstract class StateAnswer implements StateAnswerInterface
{
    use ResolvesParameters;

    protected string $type;
    protected int $perm;
    protected string $method;
    protected array $params = [];
    protected ?MessageMeta $messageMeta = null;
    protected ?StateData $stateData = null;
    protected array $bindings = [];

    protected array $allowedFields = [AllowableFields::TEXT->value];

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

    public function setMethod(string $method): void
    {
        $this->method = $method;
    }

    public function getAllowedFields(): array
    {
        return $this->allowedFields;
    }

    public function handle(): void
    {
        $command = strtolower($this->method);
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

        foreach ($parameters as $param) {
            $paramName = $param->getName();
            $type = $param->getType()?->getName();

            if ($type && class_exists($type) && is_subclass_of($type, Model::class)) {
                $column = $this->bindings[$type] ?? null;
                [$value, $found] = $this->resolveParamValue($column ?? $paramName, $paramName);
                if (!$found) {
                    throw new \Exception("Missing binding value for {$type} (\$$paramName)");
                }

                $dependencies[] = $type::where($column ?? 'id', $value)->firstOrFail();
                continue;
            }

            if ($type && class_exists($type)) {
                $dependencies[] = Container::getInstance()->make($type);
                continue;
            }

            [$value, $found] = $this->resolveParamValue($paramName);
            if ($found) {
                $dependencies[] = $value;
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

    public function messageMeta(): ?MessageMeta
    {
        if ($this->messageMeta) return $this->messageMeta;
        $messageMeta = MessageMeta::find($this->params['message_meta_id'] ?? ($this->params['message_meta'] ?? null));
        if (!$messageMeta) {
            return null;
        }
        $this->messageMeta = $messageMeta;
        return $messageMeta;
    }

    public function stateData(): ?StateData
    {
        if ($this->stateData) return $this->stateData;
        $stateData = StateData::find($this->params['state_data_id'] ?? $this->params['state_data']);
        if (!$stateData) {
            exceptionReport(new TbeLogicException('Trying to access state data, but it is not provided'));
            return null;
        }
        $this->stateData = $stateData;
        return $stateData;
    }

    public function cancel(): void
    {
        $this->messageMeta()?->revertAction();
    }

    protected function resolveParamValue(string $primary, ?string $secondary = null): array
    {
        $keys = array_keys($this->params);

        $matchedKey = $this->findMatchingKey($keys, $primary);
        if ($matchedKey !== null && array_key_exists($matchedKey, $this->params)) {
            return [$this->params[$matchedKey], true];
        }

        if ($secondary && $secondary !== $primary) {
            $matchedKey = $this->findMatchingKey($keys, $secondary);
            if ($matchedKey !== null && array_key_exists($matchedKey, $this->params)) {
                return [$this->params[$matchedKey], true];
            }
        }

        return [null, false];
    }
}
