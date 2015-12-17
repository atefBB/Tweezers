<?php

namespace Tweezers\Field;

class RadioGroup implements FormFieldInterface
{
    use ChoiceFormFieldTrait;

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
     * @param string $name The name of the field
     *
     * @throws \InvalidArgumentException When the field name is not a string
     */
    public function __construct($name)
    {
        if (!is_string($name)) {
            throw new \InvalidArgumentException(sprintf('The name of the field must be a string, %s given', gettype($name)));
        }

        $this->name = $name;
    }

    /**
     * Adds a choice to the current ones.
     *
     * This method should only be used internally.
     *
     * @param \DOMElement $node
     *
     * @throws \LogicException When node type is incorrect
     */
    public function addChoice(\DOMElement $node)
    {
        if ($node->nodeName !== 'input' or strtolower($node->getAttribute('type')) !== 'radio') {
            throw new \LogicException(sprintf('Unable to add a choice for "%s" as it is not a radio button.', $this->name));
        }

        $option = $this->buildOptionValue($node);
        $this->options[] = $option;

        if ($node->hasAttribute('checked') or ($this->value === null and !$option['disabled'])) {
            // last element index
            $this->value = $option['value'];
        }
    }
}
