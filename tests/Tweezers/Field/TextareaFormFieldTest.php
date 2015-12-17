<?php

namespace Tests\Crawler\Field;

use Tests\TestCase;
use Tweezers\Field\TextareaFormField;

class TextareaFormFieldTest extends TestCase
{
    public function testConstructorWithANonATag()
    {
        // ->initialize() throws a \LogicException if the node is not a textarea
        $this->setExpectedException('LogicException');

        $node = $this->createNode('input', '');
        $field = new TextareaFormField($node);
    }

    public function testInitialize()
    {
        $node = $this->createNode('textarea', 'foo bar');
        $field = new TextareaFormField($node);

        $this->assertEquals('foo bar', $field->getValue(), '->initialize() sets the value of the field to the textarea node value');

        // Ensure that valid HTML can be used on a textarea.
        $node = $this->createNode('textarea', 'foo bar <h1>Baz</h1>');
        $field = new TextareaFormField($node);

        $this->assertEquals('foo bar <h1>Baz</h1>', $field->getValue(), '->initialize() sets the value of the field to the textarea node value');

        // Ensure that we don't do any DOM manipulation/validation by passing in
        // "invalid" HTML.
        $node = $this->createNode('textarea', 'foo bar <h1>Baz</h2>');
        $field = new TextareaFormField($node);

        $this->assertEquals('foo bar <h1>Baz</h2>', $field->getValue(), '->initialize() sets the value of the field to the textarea node value');
    }
}
