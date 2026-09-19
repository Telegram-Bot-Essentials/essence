<?php

namespace TelegramBotEssentials\Essence\Forms;

use TelegramBotEssentials\Essence\Exceptions\LogicException;

/**
 * The forms known to the bot, by their type key and class. A singleton like
 * the other handler buses: providers register once per worker.
 */
class FormRegistry
{
    /** @var array<string, Form> */
    private array $forms = [];

    /** @param  iterable<Form|string>  $forms */
    public function addForms(iterable $forms): self
    {
        foreach ($forms as $form) {
            $this->addForm($form);
        }

        return $this;
    }

    public function addForm(Form|string $form): Form
    {
        $instance = is_string($form) ? app($form) : $form;

        if (! $instance instanceof Form) {
            throw new LogicException(sprintf('%s is not a Form.', get_debug_type($instance)));
        }

        $this->forms[$instance->getType()] = $instance;

        return $instance;
    }

    /** By type key or by class name. */
    public function get(string $typeOrClass): ?Form
    {
        if (isset($this->forms[$typeOrClass])) {
            return $this->forms[$typeOrClass];
        }

        foreach ($this->forms as $form) {
            if ($form::class === $typeOrClass) {
                return $form;
            }
        }

        return null;
    }

    /** @return array<string, Form> */
    public function all(): array
    {
        return $this->forms;
    }
}
