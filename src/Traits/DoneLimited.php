<?php

namespace TelegramBotEssentials\Essence\Traits;

use TelegramBotEssentials\Essence\Exceptions\CannotSetItAsDone;

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
                throw new CannotSetItAsDone(__('tbe::general.alerts.unableToSetDoneAttributeMissing', ['attribute' => $attribute]));
            }
        }

        $this->attributes['done'] = $value;
    }
}
