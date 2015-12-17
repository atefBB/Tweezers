<?php

namespace Tests;

use PHPUnit_Framework_TestCase;

class TestCase extends PHPUnit_Framework_TestCase
{
    protected function tearDown()
    {
        if (class_exists('Mockery')) {
            \Mockery::close();
        }
    }

    protected function loadFixture($filename)
    {
        $path = __DIR__.'/fixtures/'.$filename;

        if (file_exists($path)) {
            return file_get_contents($path);
        }

        return null;
    }

    protected function createNode($tag, $value, $attributes = array())
    {
        $document = new \DOMDocument();
        $node = $document->createElement($tag, $value);

        foreach ($attributes as $name => $value) {
            $node->setAttribute($name, $value);
        }

        return $node;
    }
}
