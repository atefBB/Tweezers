<?php

namespace Tests\Crawler\Field;

use Tests\TestCase;
use Tweezers\Field\RadioGroup;

class RadioGroupTest extends TestCase
{
    public function testConstructorWithANonATag()
    {
        $this->setExpectedException('InvalidArgumentException');

        $field = new RadioGroup(array());
    }

    public function testInitialize()
    {
        $field = new RadioGroup('foo');

        $this->assertEquals('foo', $field->getName());
    }

    public function testAddChoice()
    {
        $field = new RadioGroup('foo');

        $option = $this->createNode('input', '', array('type' => 'radio', 'name' => 'foo', 'value' => 'bar'));
        $field->addChoice($option);

        $this->assertEquals('bar', $field->getValue());
    }

    public function testAddCheckedChoice()
    {
        $field = new RadioGroup('foo');

        $option = $this->createNode('input', '', array('type' => 'radio', 'name' => 'name', 'value' => 'foo'));
        $field->addChoice($option);

        $option = $this->createNode('input', '', array('type' => 'radio', 'name' => 'name', 'value' => 'bar', 'checked' => 'checked'));
        $field->addChoice($option);

        $this->assertEquals('bar', $field->getValue());

        $option = $this->createNode('input', '', array('type' => 'radio', 'name' => 'name', 'value' => 'baz', 'checked' => ''));
        $field->addChoice($option);

        $this->assertEquals('baz', $field->getValue());
    }

    public function testHasValue()
    {
        $field = new RadioGroup('foo');

        $this->assertFalse($field->hasValue());

        $option = $this->createNode('input', '', array('type' => 'radio', 'name' => 'name', 'value' => 'foo'));
        $field->addChoice($option);

        $this->assertTrue($field->hasValue());
    }

    public function testGetValue()
    {
        $field = new RadioGroup('foo');

        $this->assertNull($field->getValue());

        $option = $this->createNode('input', '', array('type' => 'radio', 'name' => 'name', 'value' => 'foo'));
        $field->addChoice($option);

        $this->assertEquals('foo', $field->getValue());
    }

    public function testSetValue()
    {
        $field = new RadioGroup('foo');

        $option = $this->createNode('input', '', array('type' => 'radio', 'name' => 'name', 'value' => 'foo'));
        $field->addChoice($option);

        $option = $this->createNode('input', '', array('type' => 'radio', 'name' => 'name', 'value' => 'bar'));
        $field->addChoice($option);

        $this->assertEquals('foo', $field->getValue());

        // ->setValue() changes the selected option
        $field->setValue('bar');

        $this->assertEquals('bar', $field->getValue());
    }

    public function testSetNotExistingValue()
    {
        // ->setValue() throws an \InvalidArgumentException if the value is not one of the radio button values
        $this->setExpectedException('LogicException');

        $field = new RadioGroup('foo');
        $field->setValue('foobar');
    }

    public function testSelect()
    {
        $field = new RadioGroup('foo');

        $option = $this->createNode('input', '', array('type' => 'radio', 'name' => 'name', 'value' => 'foo'));
        $field->addChoice($option);

        $option = $this->createNode('input', '', array('type' => 'radio', 'name' => 'name', 'value' => 'bar'));
        $field->addChoice($option);

        $this->assertEquals('foo', $field->getValue());

        // ->select() changes the selected option
        $field->select('bar');

        $this->assertEquals('bar', $field->getValue());
    }

    public function testIsDisabled()
    {
        $field = new RadioGroup('foo');

        $option = $this->createNode('input', '', array('type' => 'radio', 'name' => 'name', 'value' => 'foo', 'disabled' => 'disabled'));
        $field->addChoice($option);

        $option = $this->createNode('input', '', array('type' => 'radio', 'name' => 'name', 'value' => 'bar'));
        $field->addChoice($option);

        $option = $this->createNode('input', '', array('type' => 'radio', 'name' => 'name', 'value' => 'baz', 'disabled' => ''));
        $field->addChoice($option);

        // ->getValue() returns the value attribute of the selected radio button
        $field->select('foo');
        $this->assertEquals('foo', $field->getValue());
        $this->assertTrue($field->isDisabled());

        // ->getValue() returns the value attribute of the selected radio button
        $field->select('bar');
        $this->assertEquals('bar', $field->getValue());
        $this->assertFalse($field->isDisabled());

        // ->getValue() returns the value attribute of the selected radio button
        $field->select('baz');
        $this->assertEquals('baz', $field->getValue());
        $this->assertTrue($field->isDisabled());
    }
}
