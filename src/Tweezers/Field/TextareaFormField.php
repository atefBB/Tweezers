<?php

namespace Tweezers\Field;

class TextareaFormField extends FormField
{
    /**
     * Returns true if the field should be included in the submitted values.
     *
     * @return bool true if the field should be included in the submitted values, false otherwise
     */
    public function hasValue()
    {
        return true;
    }

    /**
     * Initializes the form field.
     * 
     * @throws \LogicException When node type is incorrect
     */
    protected function initialize()
    {
        if ('textarea' !== $this->tag) {
            throw new \LogicException(sprintf('A TextareaFormField can only be created from a textarea tag (%s given).', $this->tag));
        }

        $this->setValue($this->text());
    }
}
