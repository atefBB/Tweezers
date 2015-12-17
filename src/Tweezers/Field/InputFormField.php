<?php

namespace Tweezers\Field;

class InputFormField extends FormField
{
    /**
     * Initializes the form field.
     *
     * @throws \LogicException When node type is incorrect
     */
    protected function initialize()
    {
        if ('input' !== $this->tag and 'button' !== $this->tag) {
            throw new \LogicException(sprintf('An InputFormField can only be created from an input or button tag (%s given).', $this->tag));
        }

        if ('checkbox' === strtolower($this->getAttribute('type'))) {
            throw new \LogicException('Checkboxes should be instances of CheckBoxFormField.');
        }

        if ('file' === strtolower($this->getAttribute('type'))) {
            throw new \LogicException('File inputs should be instances of FileFormField.');
        }

        $this->value = $this->getAttribute('value');
    }
}
