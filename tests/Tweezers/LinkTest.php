<?php

namespace Tests\Crawler;

use Tests\TestCase;
use Tweezers\Link;

class LinkTest extends TestCase
{
    /**
     * @expectedException \LogicException
     */
    public function testConstructorWithANonATag()
    {
        $node = $this->createNode('div', '');

        new Link($node, 'http://www.example.com/');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testConstructorWithAnInvalidCurrentUri()
    {
        $node = $this->createNode('a', 'foo', array('href' => '/foo'));

        new Link($node, 'example.com');
    }

    /**
     * @dataProvider getGetUriTests
     */
    public function testGetUri($url, $currentUri, $expected)
    {
        $node = $this->createNode('a', 'foo', array('href' => $url));

        $link = new Link($node, $currentUri);

        $this->assertEquals($expected, $link->getUri());
    }

    /**
     * @dataProvider getGetUriTests
     */
    public function testGetUriOnArea($url, $currentUri, $expected)
    {
        $node = $this->createNode('area', 'foo', array('href' => $url));

        $link = new Link($node, $currentUri);

        $this->assertEquals($expected, $link->getUri());
    }

    /**
     * @dataProvider getGetUriTests
     */
    public function testGetUriOnLink($url, $currentUri, $expected)
    {
        $node = $this->createNode('link', 'foo', array('href' => $url));

        $link = new Link($node, $currentUri);

        $this->assertEquals($expected, $link->getUri());
    }

    public function getGetUriTests()
    {
        return array(
            array('/foo', 'http://localhost/bar/foo/', 'http://localhost/foo'),
            array('/foo', 'http://localhost/bar/foo', 'http://localhost/foo'),
            array('
            /foo', 'http://localhost/bar/foo/', 'http://localhost/foo'),
            array('/foo
            ', 'http://localhost/bar/foo', 'http://localhost/foo'),

            array('foo', 'http://localhost/bar/foo/', 'http://localhost/bar/foo/foo'),
            array('foo', 'http://localhost/bar/foo', 'http://localhost/bar/foo'),

            array('', 'http://localhost/bar/', 'http://localhost/bar/'),
            array('#', 'http://localhost/bar/', 'http://localhost/bar/#'),
            array('#bar', 'http://localhost/bar?a=b', 'http://localhost/bar?a=b#bar'),
            array('#bar', 'http://localhost/bar/#foo', 'http://localhost/bar/#bar'),
            array('?a=b', 'http://localhost/bar#foo', 'http://localhost/bar?a=b'),
            array('?a=b', 'http://localhost/bar/', 'http://localhost/bar/?a=b'),

            array('http://login.foo.com/foo', 'http://localhost/bar/', 'http://login.foo.com/foo'),
            array('https://login.foo.com/foo', 'https://localhost/bar/', 'https://login.foo.com/foo'),
            array('mailto:foo@bar.com', 'http://localhost/foo', 'mailto:foo@bar.com'),

            // tests schema relative URL (issue #7169)
            array('//login.foo.com/foo', 'http://localhost/bar/', 'http://login.foo.com/foo'),
            array('//login.foo.com/foo', 'https://localhost/bar/', 'https://login.foo.com/foo'),

            array('?foo=2', 'http://localhost?foo=1', 'http://localhost?foo=2'),
            array('?foo=2', 'http://localhost/?foo=1', 'http://localhost/?foo=2'),
            array('?foo=2', 'http://localhost/bar?foo=1', 'http://localhost/bar?foo=2'),
            array('?foo=2', 'http://localhost/bar/?foo=1', 'http://localhost/bar/?foo=2'),
            array('?bar=2', 'http://localhost?foo=1', 'http://localhost?bar=2'),

            array('foo', 'http://login.foo.com/bar/baz?/query/string', 'http://login.foo.com/bar/foo'),

            array('.', 'http://localhost/foo/bar/baz', 'http://localhost/foo/bar/'),
            array('./', 'http://localhost/foo/bar/baz', 'http://localhost/foo/bar/'),
            array('./foo', 'http://localhost/foo/bar/baz', 'http://localhost/foo/bar/foo'),
            array('..', 'http://localhost/foo/bar/baz', 'http://localhost/foo/'),
            array('../', 'http://localhost/foo/bar/baz', 'http://localhost/foo/'),
            array('../foo', 'http://localhost/foo/bar/baz', 'http://localhost/foo/foo'),
            array('../..', 'http://localhost/foo/bar/baz', 'http://localhost/'),
            array('../../', 'http://localhost/foo/bar/baz', 'http://localhost/'),
            array('../../foo', 'http://localhost/foo/bar/baz', 'http://localhost/foo'),
            array('../../foo', 'http://localhost/bar/foo/', 'http://localhost/foo'),
            array('../bar/../../foo', 'http://localhost/bar/foo/', 'http://localhost/foo'),
            array('../bar/./../../foo', 'http://localhost/bar/foo/', 'http://localhost/foo'),
            array('../../', 'http://localhost/', 'http://localhost/'),
            array('../../', 'http://localhost', 'http://localhost/'),

            array('/foo', 'http://localhost?bar=1', 'http://localhost/foo'),
            array('/foo', 'http://localhost#bar', 'http://localhost/foo'),
            array('/foo', 'file:///', 'file:///foo'),
            array('/foo', 'file:///bar/baz', 'file:///foo'),
            array('foo', 'file:///', 'file:///foo'),
            array('foo', 'file:///bar/baz', 'file:///bar/foo'),
        );
    }
}
