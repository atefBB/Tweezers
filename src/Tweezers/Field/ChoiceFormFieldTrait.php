<?php

namespace Tweezers\Field;

trait ChoiceFormFieldTrait
{
    /**
     * @var bool
     */
    protected $validationDisabled = false;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * Returns the name of the field.
     *
     * @return string The name of the field
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Gets the value of the field.
     *
     * @return string|array The value of the field
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Sets the value of the field.
     *
     * @param string $value The value of the field
     *
     * @throws \InvalidArgumentException When value type provided is not correct
     */
    public function setValue($value)
    {
        if (!$this->containsOption($value)) {
            throw new \InvalidArgumentException(sprintf('Input "%s" cannot take "%s" as a value (possible values: %s).', $this->name, $value, implode(', ', $this->availableOptionValues())));
        }

        $this->value = $value;
    }

    /**
     * Returns true if the field should be included in the submitted values.
     *
     * @return bool true if the field should be included in the submitted values, false otherwise
     */
    public function hasValue()
    {
        return count($this->options) > 0;
    }

    /**
     * Returns option value with associated disabled flag.
     *
     * @param \DOMElement $node
     *
     * @return array
     */
    protected function buildOptionValue(\DOMElement $node)
    {
        $option = array();

        $defaultValue = (isset($node->nodeValue) and !empty($node->nodeValue)) ? $node->nodeValue : 'on';
        $option['value'] = $node->hasAttribute('value') ? $node->getAttribute('value') : $defaultValue;
        $option['disabled'] = $node->hasAttribute('disabled');

        return $option;
    }

    /**
     * Disables the internal validation of the field.
     *
     * @return self
     */
    public function disableValidation()
    {
        $this->validationDisabled = true;

        return $this;
    }

    /**
     * Returns list of available field options.
     *
     * @return array
     */
    public function availableOptionValues()
    {
        return array_column($this->options, 'value');
    }

    /**
     * Checks whether given value is in the existing options.
     *
     * @param string $optionValue
     *
     * @return bool
     */
    public function containsOption($optionValue)
    {
        if ($this->validationDisabled) {
            return true;
        }

        return in_array($optionValue, $this->availableOptionValues());
    }

    /**
     * Check if the current selected option is disabled.
     *
     * @return bool
     */
    public function isDisabled()
    {
        foreach ($this->options as $option) {
            if ($option['value'] == $this->value and $option['disabled']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sets the value of the field.
     *
     * @param string $value The value of the field
     */
    public function select($value)
    {
        $this->setValue($value);
    }
}
