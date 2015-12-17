<?php

namespace Tests\Crawler;

use Tests\TestCase;
use Tweezers\FormFieldRegistry;
use Tweezers\Field;

class FormFieldRegistryTest extends TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAddThrowAnExceptionWhenTheNameIsMalformed()
    {
        $registry = new FormFieldRegistry();
        $registry->add($this->getFormFieldMock('[foo]'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testRemoveThrowAnExceptionWhenTheNameIsMalformed()
    {
        $registry = new FormFieldRegistry();
        $registry->remove('[foo]');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetThrowAnExceptionWhenTheNameIsMalformed()
    {
        $registry = new FormFieldRegistry();
        $registry->get('[foo]');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFormFieldRegistryGetThrowAnExceptionWhenTheFieldDoesNotExist()
    {
        $registry = new FormFieldRegistry();
        $registry->get('foo');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetThrowAnExceptionWhenTheNameIsMalformed()
    {
        $registry = new FormFieldRegistry();
        $registry->set('[foo]', null);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetThrowAnExceptionWhenTheFieldDoesNotExist()
    {
        $registry = new FormFieldRegistry();
        $registry->set('foo', null);
    }

    public function testHasReturnsTrueWhenTheFQNExists()
    {
        $registry = new FormFieldRegistry();
        $registry->add($this->getFormFieldMock('foo[bar]'));

        $this->assertTrue($registry->has('foo'));
        $this->assertTrue($registry->has('foo[bar]'));
        $this->assertFalse($registry->has('bar'));
        $this->assertFalse($registry->has('foo[foo]'));
    }

    public function testFieldsCanBeRemoved()
    {
        $registry = new FormFieldRegistry();
        $registry->add($this->getFormFieldMock('foo'));
        $registry->remove('foo');
        $this->assertFalse($registry->has('foo'));
    }

    public function testSupportsMultivaluedFields()
    {
        $registry = new FormFieldRegistry();
        $registry->add($this->getFormFieldMock('foo[]'));
        $registry->add($this->getFormFieldMock('foo[]'));
        $registry->add($this->getFormFieldMock('bar[5]'));
        $registry->add($this->getFormFieldMock('bar[]'));
        $registry->add($this->getFormFieldMock('bar[baz]'));

        $this->assertEquals(
            array('foo[0]', 'foo[1]', 'bar[5]', 'bar[6]', 'bar[baz]'),
            array_keys($registry->all())
        );
    }

    public function testSetValues()
    {
        $registry = new FormFieldRegistry();
        $registry->add($f2 = $this->getFormFieldMock('foo[2]'));
        $registry->add($f3 = $this->getFormFieldMock('foo[3]'));
        $registry->add($fbb = $this->getFormFieldMock('foo[bar][baz]'));

        $f2
            ->expects($this->exactly(2))
            ->method('setValue')
            ->with(2);

        $f3
            ->expects($this->exactly(2))
            ->method('setValue')
            ->with(3);

        $fbb
            ->expects($this->exactly(2))
            ->method('setValue')
            ->with('fbb');

        $registry->set('foo[2]', 2);
        $registry->set('foo[3]', 3);
        $registry->set('foo[bar][baz]', 'fbb');

        $registry->set('foo', array(
            2 => 2,
            3 => 3,
            'bar' => array(
                'baz' => 'fbb',
             ),
        ));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Cannot set value on a compound field "foo[bar]".
     */
    public function testSetValueOnCompoundField()
    {
        $registry = new FormFieldRegistry();
        $registry->add($this->getFormFieldMock('foo[bar][baz]'));

        $registry->set('foo[bar]', 'fbb');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unreachable field "0"
     */
    public function testSetArrayOnNotCompoundField()
    {
        $registry = new FormFieldRegistry();
        $registry->add($this->getFormFieldMock('bar'));

        $registry->set('bar', array('baz'));
    }

    protected function getFormFieldMock($name, $value = null)
    {
        $field = $this
            ->getMockBuilder('Tweezers\Field\FormFieldInterface')
            ->setMethods(array('getName', 'getValue', 'setValue', 'hasValue', 'isDisabled'))
            ->disableOriginalConstructor()
            ->getMock();

        $field
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));

        $field
            ->expects($this->any())
            ->method('getValue')
            ->will($this->returnValue($value));

        return $field;
    }
}
