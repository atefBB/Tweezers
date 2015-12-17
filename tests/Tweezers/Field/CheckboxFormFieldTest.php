<?php

namespace Tests\Crawler\Field;

use Tests\TestCase;
use Tweezers\Field\CheckBoxFormField;

class CheckboxFormFieldTest extends TestCase
{
    public function testConstructorWithANonATag()
    {
        // ->initialize() throws a \LogicException if the node is not an input
        $this->setExpectedException('LogicException');

        $node = $this->createNode('textarea', '');
        $field = new CheckBoxFormField($node);
    }

    public function testInitializeWithInvalidInputType()
    {
        // ->initialize() throws a \LogicException if the node is not a checkbox
        $this->setExpectedException('LogicException');

        $node = $this->createNode('input', '', array('type' => 'text'));
        $field = new CheckBoxFormField($node);
    }

    public function testHasValue()
    {
        $node = $this->createNode('input', '', array('type' => 'checkbox', 'name' => 'name'));
        $field = new CheckBoxFormField($node);

        // ->hasValue() returns false when the checkbox is not checked
        $this->assertFalse($field->hasValue());

        $node = $this->createNode('input', '', array('type' => 'checkbox', 'name' => 'name', 'checked' => 'checked'));
        $field = new CheckBoxFormField($node);

        // ->hasValue() returns true when the checkbox is checked
        $this->assertTrue($field->hasValue());
    }

    public function testGetValue()
    {
        $node = $this->createNode('input', '', array('type' => 'checkbox', 'name' => 'name'));
        $field = new CheckBoxFormField($node);

        // ->getValue() returns null if the checkbox is not checked
        $this->assertNull($field->getValue());

        $node = $this->createNode('input', '', array('type' => 'checkbox', 'name' => 'name', 'checked' => 'checked'));
        $field = new CheckBoxFormField($node);

        // ->getValue() returns 1 if the checkbox is checked and has no value attribute
        $this->assertEquals('on', $field->getValue());
    }

    public function testSetValue()
    {
        $node = $this->createNode('input', '', array('type' => 'checkbox', 'name' => 'name', 'checked' => 'checked', 'value' => 'foo'));
        $field = new CheckBoxFormField($node);

        // ->setValue() unchecks the checkbox is value is false
        $field->setValue(false);
        $this->assertNull($field->getValue());

        // ->setValue() checks the checkbox is value is true
        $field->setValue(true);
        $this->assertEquals('foo', $field->getValue());
    }

    public function testChecked()
    {
        $node = $this->createNode('input', '', array('type' => 'checkbox', 'name' => 'name'));
        $field = new CheckBoxFormField($node);

        // ->checked() returns false when the checkbox is not checked
        $this->assertFalse($field->checked());

        $node = $this->createNode('input', '', array('type' => 'checkbox', 'name' => 'name', 'checked' => 'checked'));
        $field = new CheckBoxFormField($node);

        // ->checked() returns true when the checkbox is checked
        $this->assertTrue($field->checked());
    }

    public function testCheckboxWithEmptyBooleanAttribute()
    {
        $node = $this->createNode('input', '', array('type' => 'checkbox', 'name' => 'name', 'value' => 'foo', 'checked' => ''));
        $field = new CheckBoxFormField($node);

        $this->assertTrue($field->hasValue(), '->hasValue() returns true when the checkbox is checked');
        $this->assertEquals('foo', $field->getValue());
    }

    public function testTick()
    {
        $node = $this->createNode('input', '', array('type' => 'checkbox', 'name' => 'name'));
        $field = new CheckBoxFormField($node);

        // ->tick() ticks checkboxes
        $field->tick();
        $this->assertEquals('on', $field->getValue());
    }

    public function testUntick()
    {
        $node = $this->createNode('input', '', array('type' => 'checkbox', 'name' => 'name', 'checked' => 'checked'));
        $field = new CheckBoxFormField($node);

        // ->untick() unticks checkboxes
        $field->untick();
        $this->assertNull($field->getValue());
    }
}
