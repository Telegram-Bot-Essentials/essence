<?php

namespace Elyar\TelegramBotEssentials\Traits;


use Elyar\TelegramBotEssentials\Exceptions\CannotSetItAsDone;

trait DoneLimited
{
    /**
     * @throws CannotSetItAsDone
     */
    public function done(): void
    {
        $this->setAttributeDone(true);
        $this->save();
    }

    /**
     * @throws CannotSetItAsDone
     */
    public function setAttributeDone(bool $value): void
    {
        foreach ($this->requiredAttributes ?? [] as $attribute) {
            if (empty($this->getAttribute($attribute))) {
                throw new CannotSetItAsDone("Required attribute '{$attribute}' is missing or empty.");
            }
        }

        $this->attributes['done'] = $value;
    }
}
