<?php

namespace Tests\Crawler\Field;

use Tests\TestCase;
use Tweezers\Field\SelectFormField;

class SelectFormFieldTest extends TestCase
{
    public function testConstructorWithANonATag()
    {
        // ->initialize() throws a \LogicException if the node is not a select
        $this->setExpectedException('LogicException');

        $node = $this->createNode('textarea', '');
        $field = new SelectFormField($node);
    }

    public function testIsMultiple()
    {
        $node = $this->createNode('select', '');
        $field = new SelectFormField($node);

        // ->isMultiple() returns false for selects without the multiple attribute
        $this->assertFalse($field->isMultiple());

        $node = $this->createNode('select', '', array('multiple' => 'multiple'));
        $field = new SelectFormField($node);

        // ->isMultiple() returns true for selects with the multiple attribute
        $this->assertTrue($field->isMultiple());

        $node = $this->createNode('select', '', array('multiple' => ''));
        $field = new SelectFormField($node);

        // ->isMultiple() returns true for selects with an empty multiple attribute
        $this->assertTrue($field->isMultiple());
    }

    public function testHasValue()
    {
        $node = $this->createSelectNode(array());
        $field = new SelectFormField($node);

        // ->hasValue() returns false when no option
        $this->assertFalse($field->hasValue());

        $node = $this->createSelectNode(array('foo' => false, 'bar' => false));
        $field = new SelectFormField($node);

        // ->hasValue() returns true when select has at least one option
        $this->assertTrue($field->hasValue());
    }

    public function testGetValue()
    {
        $node = $this->createSelectNode(array('foo' => false, 'bar' => false));
        $field = new SelectFormField($node);

        // ->getValue() returns the first option if none are selected
        $this->assertEquals('foo', $field->getValue());
    }

    public function testSetValue()
    {
        $node = $this->createSelectNode(array('foo' => false, 'bar' => true));
        $field = new SelectFormField($node);

        // ->getValue() returns the selected option
        $this->assertEquals('bar', $field->getValue());

        // ->setValue() changes the selected option
        $field->setValue('foo');
        $this->assertEquals('foo', $field->getValue());
    }

    public function testSetNotExistingValue()
    {
        // ->setValue() throws an \InvalidArgumentException if the value is not one of the selected options
        $this->setExpectedException('LogicException');

        $node = $this->createSelectNode(array('foo' => false, 'bar' => true));
        $field = new SelectFormField($node);

        $field->setValue('foobar');
    }

    public function testSetArrayWhenNotMultiple()
    {
        // ->setValue() throws an \InvalidArgumentException if the value is an array
        $this->setExpectedException('LogicException');

        $node = $this->createSelectNode(array('foo' => false, 'bar' => true));
        $field = new SelectFormField($node);

        $field->setValue(array('foobar'));
    }

    public function testSelectWithEmptyBooleanAttribute()
    {
        $node = $this->createSelectNode(array('foo' => false, 'bar' => true), array(), '');
        $field = new SelectFormField($node);

        $this->assertEquals('bar', $field->getValue());
    }

    public function testSetNotExistingMultipleValue()
    {
        $this->setExpectedException('LogicException');

        $node = $this->createSelectNode(array('foo' => false, 'bar' => true), array(), '');
        $field = new SelectFormField($node);

        $field->setValue(array('foobar'));
    }

    public function testMultipleSelects()
    {
        $node = $this->createSelectNode(array('foo' => false, 'bar' => false), array('multiple' => 'multiple'));
        $field = new SelectFormField($node);

        // ->setValue() returns an empty array if multiple is true and no option is selected
        $this->assertEquals(array(), $field->getValue());

        // ->setValue() returns an array of options if multiple is true
        $field->setValue('foo');
        $this->assertEquals(array('foo'), $field->getValue());

        // ->setValue() returns an array of options if multiple is true
        $field->setValue('bar');
        $this->assertEquals(array('bar'), $field->getValue());

        // ->setValue() returns an array of options if multiple is true
        $field->setValue(array('foo', 'bar'));
        $this->assertEquals(array('foo', 'bar'), $field->getValue());

        $node = $this->createSelectNode(array('foo' => true, 'bar' => true), array('multiple' => 'multiple'));
        $field = new SelectFormField($node);

        // ->getValue() returns the selected options
        $this->assertEquals(array('foo', 'bar'), $field->getValue());
    }

    public function testSelect()
    {
        $node = $this->createSelectNode(array('foo' => false, 'bar' => false));
        $field = new SelectFormField($node);

        // ->select() changes the selected option
        $field->select('foo');
        $this->assertEquals('foo', $field->getValue());
    }

    public function testOptionWithNoValue()
    {
        $node = $this->createSelectNodeWithEmptyOption(array('foo' => false, 'bar' => false));
        $field = new SelectFormField($node);

        $this->assertEquals('foo', $field->getValue());

        $node = $this->createSelectNodeWithEmptyOption(array('foo' => false, 'bar' => true));
        $field = new SelectFormField($node);

        $this->assertEquals('bar', $field->getValue());

        // ->select() changes the selected option
        $field->select('foo');
        $this->assertEquals('foo', $field->getValue());
    }

    public function testDisableValidation()
    {
        $node = $this->createSelectNode(array('foo' => false, 'bar' => false));
        $field = new SelectFormField($node);

        $field->disableValidation();
        $field->setValue('foobar');

        // ->disableValidation() allows to set a value which is not in the selected options.
        $this->assertEquals('foobar', $field->getValue());

        $node = $this->createSelectNode(array('foo' => false, 'bar' => false), array('multiple' => 'multiple'));
        $field = new SelectFormField($node);

        $field->disableValidation();
        $field->setValue(array('foobar'));

        $this->assertEquals(array('foobar'), $field->getValue());
    }

    protected function createSelectNodeWithEmptyOption($options, $attributes = array())
    {
        $document = new \DOMDocument();
        $node = $document->createElement('select');

        foreach ($attributes as $name => $value) {
            $node->setAttribute($name, $value);
        }

        $node->setAttribute('name', 'name');

        foreach ($options as $value => $selected) {
            $option = $document->createElement('option', $value);

            if ($selected) {
                $option->setAttribute('selected', 'selected');
            }

            $node->appendChild($option);
        }

        return $node;
    }

    protected function createSelectNode($options, $attributes = array(), $selectedAttrText = 'selected')
    {
        $document = new \DOMDocument();
        $node = $document->createElement('select');

        foreach ($attributes as $name => $value) {
            $node->setAttribute($name, $value);
        }

        $node->setAttribute('name', 'name');

        foreach ($options as $value => $selected) {
            $option = $document->createElement('option', $value);
            $option->setAttribute('value', $value);

            if ($selected) {
                $option->setAttribute('selected', $selectedAttrText);
            }

            $node->appendChild($option);
        }

        return $node;
    }
}
