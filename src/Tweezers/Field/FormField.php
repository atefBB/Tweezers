<?php

namespace Tweezers\Field;

use Tweezers\Element;

abstract class FormField extends Element implements FormFieldInterface
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $value;

    /**
     * Constructor.
     *
     * @param \DOMElement $node The node associated with this field
     */
    public function __construct(\DOMElement $node)
    {
        parent::__construct($node);

        $this->name = $node->getAttribute('name');
        $this->initialize();
    }

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
     * @return string The value of the field
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Sets the value of the field.
     *
     * @param string $value The value of the field
     */
    public function setValue($value)
    {
        $this->value = (string) $value;
    }

    /**
     * Returns true if the field should be included in the submitted values.
     *
     * @return bool true if the field should be included in the submitted values, false otherwise
     */
    public function hasValue()
    {
        return $this->value !== null;
    }

    /**
     * Check if the current field is disabled.
     *
     * @return bool
     */
    public function isDisabled()
    {
        return $this->hasAttribute('disabled');
    }

    /**
     * Initializes the form field.
     */
    abstract protected function initialize();
}
