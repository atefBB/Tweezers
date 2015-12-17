<?php

namespace Tweezers\Field;

class CheckBoxFormField extends FormField
{
    /**
     * Ticks a checkbox.
     */
    public function tick()
    {
        $this->setValue(true);
    }

    /**
     * Ticks a checkbox.
     */
    public function untick()
    {
        $this->setValue(false);
    }

    /**
     * Returns true if the checkbox is marked.
     *
     * @return bool
     */
    public function checked()
    {
        return $this->hasValue();
    }

    /**
     * Sets the value of the field.
     *
     * @param bool $value The value of the field
     */
    public function setValue($value)
    {
        $this->value = $value ? $this->getAttribute('value', 'on') : null;
    }

    /**
     * Initializes the form field.
     *
     * @throws \LogicException When node type is incorrect
     */
    protected function initialize()
    {
        if ($this->tag !== 'input') {
            throw new \LogicException(sprintf('A CheckBoxFormField can only be created from an input tag (%s given).', $this->tag));
        }

        if (strtolower($this->getAttribute('type')) !== 'checkbox') {
            throw new \LogicException(sprintf('A CheckBoxFormField can only be created from an input tag with a type of checkbox (given type is %s).', $this->getAttribute('type')));
        }

        $this->setValue($this->hasAttribute('checked'));
    }
}
