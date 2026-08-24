<?php

namespace TelegramBotEssentials\Essence\Traits;

use TelegramBotEssentials\Essence\Exceptions\CannotSetItActive;

trait CanBeActivated
{
    public function scopeActive($query)
    {
        $model = $query->getModel();

        $query->where('active', true);

        foreach ($model->requiredAttributes ?? [] as $attribute) {
            $query->whereNotNull($attribute);
        }

        return $query;
    }

    /**
     * @throws CannotSetItActive
     */
    public function setActiveAttribute($value): void
    {
        if ($value) {
            foreach ($this->requiredAttributes ?? [] as $attribute) {
                if (empty($this->attributes[$attribute])) {
                    throw new CannotSetItActive(__('tbe::general.alerts.unableToActivateAttributeMissing', ['attribute' => $attribute]));
                }
            }
        }

        $this->attributes['active'] = $value;
    }
}
