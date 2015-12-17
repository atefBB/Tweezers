<?php

namespace Tests\Crawler\Field;

use Tests\TestCase;
use Tweezers\Field\InputFormField;

class InputFormFieldTest extends TestCase
{
    public function testConstructorWithANonATag()
    {
        // ->initialize() throws a \LogicException if the node is not an input
        $this->setExpectedException('LogicException');

        $node = $this->createNode('textarea', '');
        $field = new InputFormField($node);
    }

    public function testInitializeWithCheckbox()
    {
        // ->initialize() throws a \LogicException if the node is a checkbox
        $this->setExpectedException('LogicException');

        $node = $this->createNode('input', '', array('type' => 'checkbox'));
        $field = new InputFormField($node);
    }

    public function testInitializeWithFileInput()
    {
        // ->initialize() throws a \LogicException if the node is a file
        $this->setExpectedException('LogicException');

        $node = $this->createNode('input', '', array('type' => 'file'));
        $field = new InputFormField($node);
    }

    public function testInitialize()
    {
        $node = $this->createNode('input', '', array('type' => 'text', 'name' => 'name', 'value' => 'value'));
        $field = new InputFormField($node);

        $this->assertEquals('value', $field->getValue(), '->initialize() sets the value of the field to the value attribute value');
    }
}
