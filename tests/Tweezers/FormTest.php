<?php

namespace Tests\Crawler;

use Tests\TestCase;
use Tweezers\Form;
use Tweezers\Field;

class FormTest extends TestCase
{
    public function testConstructorWithANonATag()
    {
        // ->initialize() throws a \LogicException if the node is not an form
        $this->setExpectedException('LogicException');

        $node = $this->createNode('textarea', '');
        $field = new Form($node, 'http://example.com/');
    }

    /**
     * @dataProvider provideInitializeValues
     */
    public function testConstructor($message, $form, $values)
    {
        $form = $this->createForm('<form>'.$form.'</form>');
        $this->assertEquals(
            $values,
            array_map(function ($field) {
                    $class = get_class($field);

                    return array(substr($class, strrpos($class, '\\') + 1), $field->getValue());
                },
                $form->allFields()
            ),
            '->getDefaultValues() '.$message
        );
    }

    public function provideInitializeValues()
    {
        return array(
            array(
                'does not take into account input fields without a name attribute',
                '<input type="text" value="foo" />
                 <input type="submit" />',
                array(),
            ),
            array(
                'does not take into account input fields with an empty name attribute value',
                '<input type="text" name="" value="foo" />
                 <input type="submit" />',
                array(),
            ),
            array(
                'takes into account disabled input fields',
                '<input type="text" name="foo" value="foo" disabled="disabled" />
                 <input type="submit" />',
                array('foo' => array('InputFormField', 'foo')),
            ),
            array(
                'returns textareas',
                '<textarea name="foo">foo</textarea>
                 <input type="submit" />',
                 array('foo' => array('TextareaFormField', 'foo')),
            ),
            array(
                'returns inputs',
                '<input type="text" name="foo" value="foo" />
                 <input type="submit" />',
                 array('foo' => array('InputFormField', 'foo')),
            ),
            array(
                'returns checkboxes',
                '<input type="checkbox" name="foo" value="foo" checked="checked" />
                 <input type="submit" />',
                 array('foo' => array('CheckBoxFormField', 'foo')),
            ),
            array(
                'returns not-checked checkboxes',
                '<input type="checkbox" name="foo" value="foo" />
                 <input type="submit" />',
                 array('foo' => array('CheckBoxFormField', false)),
            ),
            array(
                'returns radio buttons',
                '<input type="radio" name="foo" value="foo" />
                 <input type="radio" name="foo" value="bar" checked="bar" />
                 <input type="submit" />',
                 array('foo' => array('RadioGroup', 'bar')),
            ),
            array(
                'returns file inputs',
                '<input type="file" name="foo" />
                 <input type="submit" />',
                 array('foo' => array('FileFormField', array('name' => '', 'type' => '', 'tmp_name' => '', 'error' => 4, 'size' => 0))),
            ),
        );
    }

    public function testMultiValuedFields()
    {
        $form = $this->createForm('<form>
            <input type="text" name="foo[4]" value="foo" disabled="disabled" />
            <input type="text" name="foo" value="foo" disabled="disabled" />
            <input type="text" name="foo[2]" value="foo" disabled="disabled" />
            <input type="text" name="foo[]" value="foo" disabled="disabled" />
            <input type="text" name="bar[foo][]" value="foo" disabled="disabled" />
            <input type="text" name="bar[foo][foobar]" value="foo" disabled="disabled" />
            <input type="submit" />
        </form>
        ');

        $this->assertEquals(
            array_keys($form->allFields()),
            array('foo[2]', 'foo[3]', 'bar[foo][0]', 'bar[foo][foobar]')
        );

        $this->assertEquals($form->getField('foo[2]')->getValue(), 'foo');
        $this->assertEquals($form->getField('foo[3]')->getValue(), 'foo');
        $this->assertEquals($form->getField('bar[foo][0]')->getValue(), 'foo');
        $this->assertEquals($form->getField('bar[foo][foobar]')->getValue(), 'foo');

        $form['foo[2]'] = 'bar';
        $form['foo[3]'] = 'bar';

        $this->assertEquals($form->getField('foo[2]')->getValue(), 'bar');
        $this->assertEquals($form->getField('foo[3]')->getValue(), 'bar');

        $form['bar'] = array('foo' => array('0' => 'bar', 'foobar' => 'foobar'));

        $this->assertEquals($form->getField('bar[foo][0]')->getValue(), 'bar');
        $this->assertEquals($form->getField('bar[foo][foobar]')->getValue(), 'foobar');
    }

    public function testGetMethod()
    {
        $form = $this->createForm('<form><input type="submit" /></form>');
        $this->assertEquals('GET', $form->getMethod());

        $form = $this->createForm('<form method="post"><input type="submit" /></form>');
        $this->assertEquals('POST', $form->getMethod());

        $form = $this->createForm('<form method="post"><input type="submit" /></form>', 'put');
        $this->assertEquals('PUT', $form->getMethod());

        $form = $this->createForm('<form method="post"><input type="submit" /></form>', 'delete');
        $this->assertEquals('DELETE', $form->getMethod());

        $form = $this->createForm('<form method="post"><input type="submit" /></form>', 'patch');
        $this->assertEquals('PATCH', $form->getMethod());
    }

    public function testGetFiles()
    {
        $form = $this->createForm('<form><input type="file" name="foo[bar]" /><input type="text" name="bar" value="bar" /><input type="submit" /></form>');
        $this->assertEquals(array(), $form->getFiles());

        $form = $this->createForm('<form method="post"><input type="file" name="foo[bar]" /><input type="text" name="bar" value="bar" /><input type="submit" /></form>');
        $this->assertEquals(array('foo[bar]' => array('name' => '', 'type' => '', 'tmp_name' => '', 'error' => 4, 'size' => 0)), $form->getFiles());

        $form = $this->createForm('<form method="post"><input type="file" name="foo[bar]" /><input type="text" name="bar" value="bar" /><input type="submit" /></form>', 'put');
        $this->assertEquals(array('foo[bar]' => array('name' => '', 'type' => '', 'tmp_name' => '', 'error' => 4, 'size' => 0)), $form->getFiles());

        $form = $this->createForm('<form method="post"><input type="file" name="foo[bar]" /><input type="text" name="bar" value="bar" /><input type="submit" /></form>', 'delete');
        $this->assertEquals(array('foo[bar]' => array('name' => '', 'type' => '', 'tmp_name' => '', 'error' => 4, 'size' => 0)), $form->getFiles());

        $form = $this->createForm('<form method="post"><input type="file" name="foo[bar]" /><input type="text" name="bar" value="bar" /><input type="submit" /></form>', 'patch');
        $this->assertEquals(array('foo[bar]' => array('name' => '', 'type' => '', 'tmp_name' => '', 'error' => 4, 'size' => 0)), $form->getFiles());

        $form = $this->createForm('<form method="post"><input type="file" name="foo[bar]" disabled="disabled" /><input type="submit" /></form>');
        $this->assertEquals(array(), $form->getFiles());
    }

    public function testGetPhpFiles()
    {
        $form = $this->createForm('<form method="post"><input type="file" name="foo[bar]" /><input type="text" name="bar" value="bar" /><input type="submit" /></form>');
        $this->assertEquals(array('foo' => array('bar' => array('name' => '', 'type' => '', 'tmp_name' => '', 'error' => 4, 'size' => 0))), $form->getPhpFiles());

        $form = $this->createForm('<form method="post"><input type="file" name="f.o o[bar]" /><input type="text" name="bar" value="bar" /><input type="submit" /></form>');
        $this->assertEquals(array('f.o o' => array('bar' => array('name' => '', 'type' => '', 'tmp_name' => '', 'error' => 4, 'size' => 0))), $form->getPhpFiles());

        $form = $this->createForm('<form method="post"><input type="file" name="f.o o[bar][ba.z]" /><input type="file" name="f.o o[bar][]" /><input type="text" name="bar" value="bar" /><input type="submit" /></form>');
        $this->assertEquals(array('f.o o' => array('bar' => array('ba.z' => array('name' => '', 'type' => '', 'tmp_name' => '', 'error' => 4, 'size' => 0), array('name' => '', 'type' => '', 'tmp_name' => '', 'error' => 4, 'size' => 0)))), $form->getPhpFiles());
    }

    /**
     * @dataProvider provideGetUriValues
     */
    public function testGetUri($message, $form, $values, $uri, $method = null)
    {
        $form = $this->createForm($form, $method);
        $form->setValues($values);

        $this->assertEquals('http://example.com'.$uri, $form->getUri(), '->getUri() '.$message);
    }

    public function testGetBaseUri()
    {
        $html = '<form method="post" action="foo.php"><input type="text" name="bar" value="bar" /><input type="submit" /></form>';

        $nodes = $this->getElementsByTagName($html, 'form');
        $form = new Form($nodes->item($nodes->length - 1), 'http://www.foo.com/');
        $this->assertEquals('http://www.foo.com/foo.php', $form->getUri());
    }

    public function testGetUriWithAnchor()
    {
        $form = $this->createForm('<form action="#foo"><input type="submit" /></form>', null, 'http://example.com/id/123');

        $this->assertEquals('http://example.com/id/123#foo', $form->getUri());
    }

    public function testGetUriActionAbsolute()
    {
        $formHtml = '<form id="login_form" action="https://login.foo.com/login.php?login_attempt=1" method="POST"><input type="text" name="foo" value="foo" /><input type="submit" /></form>';

        $form = $this->createForm($formHtml);
        $this->assertEquals('https://login.foo.com/login.php?login_attempt=1', $form->getUri());

        $form = $this->createForm($formHtml, null, 'https://login.foo.com');
        $this->assertEquals('https://login.foo.com/login.php?login_attempt=1', $form->getUri());

        $form = $this->createForm($formHtml, null, 'https://login.foo.com/bar/');
        $this->assertEquals('https://login.foo.com/login.php?login_attempt=1', $form->getUri());

        // The action URI haven't the same domain Host have an another domain as Host
        $form = $this->createForm($formHtml, null, 'https://www.foo.com');
        $this->assertEquals('https://login.foo.com/login.php?login_attempt=1', $form->getUri());

        $form = $this->createForm($formHtml, null, 'https://www.foo.com/bar/');
        $this->assertEquals('https://login.foo.com/login.php?login_attempt=1', $form->getUri());
    }

    public function testGetUriAbsolute()
    {
        $form = $this->createForm('<form action="foo"><input type="submit" /></form>', null, 'http://localhost/foo/');
        $this->assertEquals('http://localhost/foo/foo', $form->getUri());

        $form = $this->createForm('<form action="/foo"><input type="submit" /></form>', null, 'http://localhost/foo/');
        $this->assertEquals('http://localhost/foo', $form->getUri());
    }

    public function testGetUriWithOnlyQueryString()
    {
        $form = $this->createForm('<form action="?get=param"><input type="submit" /></form>', null, 'http://localhost/foo/bar');
        $this->assertEquals('http://localhost/foo/bar?get=param', $form->getUri());
    }

    public function testGetUriWithoutAction()
    {
        $form = $this->createForm('<form><input type="submit" /></form>', null, 'http://localhost/foo/bar');
        $this->assertEquals('http://localhost/foo/bar', $form->getUri());
    }

    public function provideGetUriValues()
    {
        return array(
            array(
                'returns the URI of the form',
                '<form action="/foo"><input type="submit" /></form>',
                array(),
                '/foo',
            ),
            array(
                'appends the form values if the method is get',
                '<form action="/foo"><input type="text" name="foo" value="foo" /><input type="submit" /></form>',
                array(),
                '/foo?foo=foo',
            ),
            array(
                'appends the form values and merges the submitted values',
                '<form action="/foo"><input type="text" name="foo" value="foo" /><input type="submit" /></form>',
                array('foo' => 'bar'),
                '/foo?foo=bar',
            ),
            array(
                'does not append values if the method is post',
                '<form action="/foo" method="post"><input type="text" name="foo" value="foo" /><input type="submit" /></form>',
                array(),
                '/foo',
            ),
            array(
                'does not append values if the method is patch',
                '<form action="/foo" method="post"><input type="text" name="foo" value="foo" /><input type="submit" /></form>',
                array(),
                '/foo',
                'PUT',
            ),
            array(
                'does not append values if the method is delete',
                '<form action="/foo" method="post"><input type="text" name="foo" value="foo" /><input type="submit" /></form>',
                array(),
                '/foo',
                'DELETE',
            ),
            array(
                'does not append values if the method is put',
                '<form action="/foo" method="post"><input type="text" name="foo" value="foo" /><input type="submit" /></form>',
                array(),
                '/foo',
                'PATCH',
            ),
            array(
                'appends the form values to an existing query string',
                '<form action="/foo?bar=bar"><input type="text" name="foo" value="foo" /><input type="submit" /></form>',
                array(),
                '/foo?bar=bar&foo=foo',
            ),
            array(
                'replaces query values with the form values',
                '<form action="/foo?bar=bar"><input type="text" name="bar" value="foo" /><input type="submit" /></form>',
                array(),
                '/foo?bar=foo',
            ),
            array(
                'returns an empty URI if the action is empty',
                '<form><input type="submit" /></form>',
                array(),
                '/',
            ),
            array(
                'appends the form values even if the action is empty',
                '<form><input type="text" name="foo" value="foo" /><input type="submit" /></form>',
                array(),
                '/?foo=foo',
            ),
            array(
                'chooses the path if the action attribute value is a sharp (#)',
                '<form action="#" method="post"><input type="text" name="foo" value="foo" /><input type="submit" /></form>',
                array(),
                '/#',
            ),
        );
    }

    public function testHasField()
    {
        $form = $this->createForm('<form method="post"><input type="text" name="bar" value="bar" /><input type="submit" /></form>');

        $this->assertFalse($form->hasField('foo'));
        $this->assertTrue($form->hasField('bar'));
    }

    public function testGetField()
    {
        $form = $this->createForm('<form method="post"><input type="text" name="bar" value="bar" /><input type="submit" /></form>');

        $this->assertInstanceOf('Tweezers\Field\InputFormField', $form->getField('bar'), '->get() returns the field object associated with the given name');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetNotExistingField()
    {
        $form = $this->createForm('<form method="post"><input type="text" name="bar" value="bar" /><input type="submit" /></form>');

        $form->getField('foo');
    }

    public function testRemoveField()
    {
        $form = $this->createForm('<form method="post"><input type="text" name="bar" value="bar" /><input type="submit" /></form>');

        $form->removeField('bar');
        $this->assertFalse($form->hasField('bar'));
    }

    public function testAllFields()
    {
        $form = $this->createForm('<form method="post"><input type="text" name="bar" value="bar" /><input type="submit" /></form>');

        $fields = $form->allFields();

        $this->assertCount(1, $fields);
        $this->assertInstanceOf('Tweezers\Field\InputFormField', $fields['bar']);
    }

    public function testDisableValidation()
    {
        $form = $this->createForm('<form>
            <select name="foo[bar]">
                <option value="bar">bar</option>
            </select>
            <select name="foo[baz]">
                <option value="foo">foo</option>
            </select>
            <input type="submit" />
        </form>');

        $form->disableValidation();

        $form['foo[bar]']->select('foo');
        $form['foo[baz]']->select('bar');
        $this->assertEquals('foo', $form['foo[bar]']->getValue(), '->disableValidation() disables validation of all ChoiceFormField.');
        $this->assertEquals('bar', $form['foo[baz]']->getValue(), '->disableValidation() disables validation of all ChoiceFormField.');
    }

    public function testOffsetUnset()
    {
        $form = $this->createForm('<form><input type="text" name="foo" value="foo" /><input type="submit" /></form>');
        unset($form['foo']);
        $this->assertFalse(isset($form['foo']), '->offsetUnset() removes a field');
    }

    public function testOffsetExists()
    {
        $form = $this->createForm('<form><input type="text" name="foo" value="foo" /><input type="submit" /></form>');

        $this->assertTrue(isset($form['foo']), '->offsetExists() return true if the field exists');
        $this->assertFalse(isset($form['bar']), '->offsetExists() return false if the field does not exist');
    }

    public function testGetValues()
    {
        $form = $this->createForm('<form><input type="text" name="foo[bar]" value="foo" /><input type="text" name="bar" value="bar" /><select multiple="multiple" name="baz[]"></select><input type="submit" /></form>');
        $this->assertEquals(array('foo[bar]' => 'foo', 'bar' => 'bar'), $form->getValues(), '->getValues() returns all form field values');

        $form = $this->createForm('<form><input type="checkbox" name="foo" value="foo" /><input type="text" name="bar" value="bar" /><input type="submit" /></form>');
        $this->assertEquals(array('bar' => 'bar'), $form->getValues(), '->getValues() does not include not-checked checkboxes');

        $form = $this->createForm('<form><input type="file" name="foo" value="foo" /><input type="text" name="bar" value="bar" /><input type="submit" /></form>');
        $this->assertEquals(array('bar' => 'bar'), $form->getValues(), '->getValues() does not include file input fields');

        $form = $this->createForm('<form><input type="text" name="foo" value="foo" disabled="disabled" /><input type="text" name="bar" value="bar" /><input type="submit" /></form>');
        $this->assertEquals(array('bar' => 'bar'), $form->getValues(), '->getValues() does not include disabled fields');
    }

    public function testSetValues()
    {
        $form = $this->createForm('<form><input type="checkbox" name="foo" value="foo" checked="checked" /><input type="text" name="bar" value="bar" /><input type="submit" /></form>');
        $form->setValues(array('foo' => false, 'bar' => 'foo'));
        $this->assertEquals(array('bar' => 'foo'), $form->getValues(), '->setValues() sets the values of fields');
    }

    public function testMultiselectSetValues()
    {
        $form = $this->createForm('<form><select multiple="multiple" name="multi"><option value="foo">foo</option><option value="bar">bar</option></select><input type="submit" /></form>');
        $form->setValues(array('multi' => array('foo', 'bar')));
        $this->assertEquals(array('multi' => array('foo', 'bar')), $form->getValues(), '->setValue() sets the values of select');
    }

    public function testGetPhpValues()
    {
        $form = $this->createForm('<form><input type="text" name="foo[bar]" value="foo" /><input type="text" name="bar" value="bar" /><input type="submit" /></form>');
        $this->assertEquals(array('foo' => array('bar' => 'foo'), 'bar' => 'bar'), $form->getPhpValues(), '->getPhpValues() converts keys with [] to arrays');

        $form = $this->createForm('<form><input type="text" name="fo.o[ba.r]" value="foo" /><input type="text" name="ba r" value="bar" /><input type="submit" /></form>');
        $this->assertEquals(array('fo.o' => array('ba.r' => 'foo'), 'ba r' => 'bar'), $form->getPhpValues(), '->getPhpValues() preserves periods and spaces in names');

        $form = $this->createForm('<form><input type="text" name="fo.o[ba.r][]" value="foo" /><input type="text" name="fo.o[ba.r][ba.z]" value="bar" /><input type="submit" /></form>');
        $this->assertEquals(array('fo.o' => array('ba.r' => array('foo', 'ba.z' => 'bar'))), $form->getPhpValues(), '->getPhpValues() preserves periods and spaces in names recursively');

        $form = $this->createForm('<form><input type="text" name="foo[bar]" value="foo" /><input type="text" name="bar" value="bar" /><select multiple="multiple" name="baz[]"></select><input type="submit" /></form>');
        $this->assertEquals(array('foo' => array('bar' => 'foo'), 'bar' => 'bar'), $form->getPhpValues(), "->getPhpValues() doesn't return empty values");
    }

    public function testGetPhpValuesWithEmptyTextarea()
    {
        $html = '
            <html>
                <form>
                    <textarea name="example"></textarea>
                </form>
            </html>
        ';

        $node = $this->getElementsByTagName($html, 'form')->item(0);
        $form = new Form($node, 'http://example.com');

        $this->assertEquals(array('example' => ''), $form->getPhpValues());
    }

    public function testGetSetValue()
    {
        $form = $this->createForm('<form><input type="text" name="foo" value="foo" /><input type="submit" /></form>');

        $this->assertEquals('foo', $form['foo']->getValue(), '->offsetGet() returns the value of a form field');

        $form['foo'] = 'bar';

        $this->assertEquals('bar', $form['foo']->getValue(), '->offsetSet() changes the value of a form field');

        try {
            $form['foobar'] = 'bar';
            $this->fail('->offsetSet() throws an \InvalidArgumentException exception if the field does not exist');
        } catch (\InvalidArgumentException $e) {
            $this->assertTrue(true, '->offsetSet() throws an \InvalidArgumentException exception if the field does not exist');
        }

        try {
            $form['foobar'];
            $this->fail('->offsetSet() throws an \InvalidArgumentException exception if the field does not exist');
        } catch (\InvalidArgumentException $e) {
            $this->assertTrue(true, '->offsetSet() throws an \InvalidArgumentException exception if the field does not exist');
        }
    }

    public function testSetValueOnMultiValuedFieldsWithMalformedName()
    {
        $form = $this->createForm('<form><input type="text" name="foo[bar]" value="bar" /><input type="text" name="foo[baz]" value="baz" /><input type="submit" /></form>');

        try {
            $form['foo[bar'] = 'bar';
            $this->fail('->offsetSet() throws an \InvalidArgumentException exception if the name is malformed.');
        } catch (\InvalidArgumentException $e) {
            $this->assertTrue(true, '->offsetSet() throws an \InvalidArgumentException exception if the name is malformed.');
        }
    }

    public function testDifferentFieldTypesWithSameName()
    {
        $html = '
            <html>
                <body>
                    <form action="/">
                        <input type="hidden" name="option" value="default">
                        <input type="radio" name="option" value="A">
                        <input type="radio" name="option" value="B">
                        <input type="hidden" name="settings[1]" value="0">
                        <input type="checkbox" name="settings[1]" value="1" id="setting-1">
                        <button>klickme</button>
                    </form>
                </body>
            </html>
        ';

        $form = new Form($this->getElementsByTagName($html, 'form')->item(0), 'http://example.com');

        $this->assertInstanceOf('Tweezers\Field\RadioGroup', $form->getField('option'));
    }

    protected function getElementsByTagName($html, $tagName)
    {
        $dom = new \DOMDocument();

        $dom->loadHTML($html);

        return $dom->getElementsByTagName($tagName);
    }

    protected function createForm($form, $method = null, $currentUri = null)
    {
        $dom = new \DOMDocument();
        $dom->loadHTML('<html>'.$form.'</html>');

        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query('//form');

        if (null === $currentUri) {
            $currentUri = 'http://example.com/';
        }

        return new Form($nodes->item($nodes->length - 1), $currentUri, $method);
    }

    protected function createTestHtml5Form()
    {
        $dom = new \DOMDocument();
        $dom->loadHTML('
        <html>
            <h1>Hello form</h1>
            <form id="form-1" action="" method="POST">
                <div><input type="checkbox" name="apples[]" value="1" checked /></div>
                <input form="form_2" type="checkbox" name="oranges[]" value="1" checked />
                <div><label></label><input form="form-1" type="hidden" name="form_name" value="form-1" /></div>
                <input form="form-1" type="submit" name="button_1" value="Capture fields" />
                <button form="form_2" type="submit" name="button_2">Submit form_2</button>
            </form>
            <input form="form-1" type="checkbox" name="apples[]" value="2" checked />
            <form id="form_2" action="" method="POST">
                <div><div><input type="checkbox" name="oranges[]" value="2" checked />
                <input type="checkbox" name="oranges[]" value="3" checked /></div></div>
                <input form="form_2" type="hidden" name="form_name" value="form_2" />
                <input form="form-1" type="hidden" name="outer_field" value="success" />
                <button form="form-1" type="submit" name="button_3">Submit from outside the form</button>
                <div>
                    <label for="app_frontend_form_type_contact_form_type_contactType">Message subject</label>
                    <div>
                        <select name="app_frontend_form_type_contact_form_type[contactType]" id="app_frontend_form_type_contact_form_type_contactType"><option selected="selected" value="">Please select subject</option><option id="1">Test type</option></select>
                    </div>
                </div>
                <div>
                    <label for="app_frontend_form_type_contact_form_type_firstName">Firstname</label>
                    <input type="text" name="app_frontend_form_type_contact_form_type[firstName]" value="John" id="app_frontend_form_type_contact_form_type_firstName"/>
                </div>
            </form>
            <button />
        </html>');

        return $dom;
    }

    protected function createTestMultipleForm()
    {
        $dom = new \DOMDocument();
        $dom->loadHTML('
        <html>
            <h1>Hello form</h1>
            <form action="" method="POST">
                <div><input type="checkbox" name="apples[]" value="1" checked /></div>
                <input type="checkbox" name="oranges[]" value="1" checked />
                <div><label></label><input type="hidden" name="form_name" value="form-1" /></div>
                <input type="submit" name="button_1" value="Capture fields" />
                <button type="submit" name="button_2">Submit form_2</button>
            </form>
            <form action="" method="POST">
                <div><div><input type="checkbox" name="oranges[]" value="2" checked />
                <input type="checkbox" name="oranges[]" value="3" checked /></div></div>
                <input type="hidden" name="form_name" value="form_2" />
                <input type="hidden" name="outer_field" value="success" />
                <button type="submit" name="button_3">Submit from outside the form</button>
            </form>
            <button />
        </html>');

        return $dom;
    }
}
