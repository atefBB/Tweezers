<?php

namespace Tweezers\Field;

class SelectFormField extends FormField
{
    use ChoiceFormFieldTrait;

    /**
     * @var bool
     */
    private $multiple;

    /**
     * Constructor.
     *
     * @param \DOMElement $node The node associated with this field
     */
    public function __construct(\DOMElement $node)
    {
        parent::__construct($node);
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
        if (is_array($value)) {
            if (!$this->multiple) {
                throw new \InvalidArgumentException(sprintf('The value for "%s" cannot be an array.', $this->name));
            }

            foreach ($value as $v) {
                if (!$this->containsOption($v, $this->options)) {
                    throw new \InvalidArgumentException(sprintf('Input "%s" cannot take "%s" as a value (possible values: %s).', $this->name, $v, implode(', ', $this->availableOptionValues())));
                }
            }
        } elseif (!$this->containsOption($value, $this->options)) {
            throw new \InvalidArgumentException(sprintf('Input "%s" cannot take "%s" as a value (possible values: %s).', $this->name, $value, implode(', ', $this->availableOptionValues())));
        }

        if ($this->multiple) {
            $value = (array) $value;
        }

        $this->value = $value;
    }

    /**
     * Returns true if the field accepts multiple values.
     *
     * @return bool true if the field accepts multiple values, false otherwise
     */
    public function isMultiple()
    {
        return $this->multiple;
    }

    /**
     * Adds a choice to the current ones.
     *
     * This method should only be used internally.
     *
     * @param \DOMElement $node
     */
    public function addChoice(\DOMElement $node)
    {
        $option = $this->buildOptionValue($node);
        $this->options[] = $option;

        if ($node->hasAttribute('selected')) {
            if ($this->multiple) {
                $this->value[] = $option['value'];
            } else {
                $this->value = $option['value'];
            }
        }
    }

    /**
     * Initializes the form field.
     *
     * @throws \LogicException When node type is incorrect
     */
    protected function initialize()
    {
        if ('select' !== $this->tag) {
            throw new \LogicException(sprintf('A SelectFormField can only be created from a select tag (%s given).', $this->tag));
        }

        $this->multiple = false;

        if ($this->hasAttribute('multiple')) {
            $this->multiple = true;
            $this->value = array();
            $this->setAttribute('name', str_replace('[]', '', $this->getAttribute('name')));
        }

        foreach ($this->xpath('descendant::option', false) as $option) {
            $this->addChoice($option);
        }

        // if no option is selected and if it is a simple select box, take the first option as the value
        if ($this->value === null and !empty($this->options)) {
            $this->value = $this->options[0]['value'];
        }
    }
}
