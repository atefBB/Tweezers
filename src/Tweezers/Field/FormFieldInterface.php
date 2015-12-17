<?php

namespace Tweezers\Field;

interface FormFieldInterface
{
    /**
     * Returns the name of the field.
     *
     * @return string The name of the field
     */
    public function getName();

    /**
     * Gets the value of the field.
     *
     * @return string|array The value of the field
     */
    public function getValue();

    /**
     * Sets the value of the field.
     *
     * @param string $value The value of the field
     */
    public function setValue($value);

    /**
     * Returns true if the field should be included in the submitted values.
     *
     * @return bool true if the field should be included in the submitted values, false otherwise
     */
    public function hasValue();

    /**
     * Check if the current field is disabled.
     *
     * @return bool
     */
    public function isDisabled();
}
