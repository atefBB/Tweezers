<?php

namespace Tests\Crawler;

use Tests\TestCase;
use Tweezers\NodeList;
use DiDom\Element;

class NodeListTest extends TestCase
{
    public function testConstructor()
    {
        $nodeList = new NodeList($this->createNodeList(3));
        $this->assertCount(3, $nodeList);

        $nodeList = new NodeList($this->createNode('div', ''));
        $this->assertCount(1, $nodeList);

        $nodeList = new NodeList(new Element($this->createNode('div', '')));
        $this->assertCount(1, $nodeList);

        $nodeList = new NodeList($this->createArrayOfNodes(3));
        $this->assertCount(3, $nodeList);

        $nodeList = new NodeList(null);
        $this->assertCount(0, $nodeList);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAddInvalidType()
    {
        $nodeList = new NodeList();
        $nodeList->add(1);
    }

    public function testAddNodeList()
    {
        $nodeList = new NodeList();
        $nodeList->addNodeList($this->createNodeList(3));

        $this->assertCount(3, $nodeList);
    }

    public function testAddNodes()
    {
        $nodeList = new NodeList();
        $nodeList->addNodes($this->createArrayOfNodes(3));

        $this->assertCount(3, $nodeList);
    }

    public function testAddNode()
    {
        $nodeList = new NodeList();
        $nodeList->addNode($this->createNodeList()->item(0));

        $this->assertCount(1, $nodeList);

        $nodeList = new NodeList();
        $nodeList->addNode(new Element($this->createNode('div', '')));

        $this->assertCount(1, $nodeList);
    }

    public function testClear()
    {
        $nodeList = new NodeList($this->createNodeList()->item(0));
        $nodeList->clear();

        $this->assertCount(0, $nodeList);
    }

    public function testHas()
    {
        $nodeList = new NodeList();

        list($firstNode, $secondNode) = $this->createArrayOfNodes(2);

        $nodeList->addNode($firstNode);

        $this->assertTrue($nodeList->has($firstNode));
        $this->assertFalse($nodeList->has($secondNode));

        $nodeList->addNode($secondNode);

        $this->assertTrue($nodeList->has($secondNode));
    }

    public function testGetNode()
    {
        $nodes = $this->filterXPath($this->loadFixture('list.html'), '//ul[1]/li');
        $nodeList = new NodeList($nodes);

        $this->assertInstanceOf('DiDom\Element', $nodeList->getNode(0));
        $this->assertEquals('One', $nodeList->getNode(0)->text());
    }

    public function testLast()
    {
        $nodes = $this->filterXPath($this->loadFixture('list.html'), '//ul[1]/li');
        $nodeList = new NodeList($nodes);

        $this->assertInstanceOf('DiDom\Element', $nodeList->last());
        $this->assertEquals('Three', $nodeList->last()->text());
    }

    public function testFirst()
    {
        $nodes = $this->filterXPath($this->loadFixture('list.html'), '//ul[1]/li');
        $nodeList = new NodeList($nodes);

        $this->assertInstanceOf('DiDom\Element', $nodeList->first());
        $this->assertEquals('One', $nodeList->first()->text());
    }

    public function testAll()
    {
        $nodes = $this->createArrayOfNodes(2);
        $nodeList = new NodeList($nodes);

        foreach ($nodeList->all() as $index => $node) {
            $this->assertEquals($nodes[$index], $node->getNode());
        }
    }

    public function testToArray()
    {
        $nodes = $this->createArrayOfNodes(2);
        $nodeList = new NodeList($nodes);

        foreach ($nodeList->toArray() as $index => $node) {
            $this->assertEquals($nodes[$index], $node->getNode());
        }
    }

    public function testCount()
    {
        $nodeList = new NodeList($this->createArrayOfNodes(3));

        $this->assertEquals(3, $nodeList->count());
    }

    public function testEach()
    {
        $nodes = $this->filterXPath($this->loadFixture('list.html'), '//ul[1]/li');
        $nodeList = new NodeList($nodes);

        $data = $nodeList->each(function ($node, $i) {
            return $i.'-'.$node->text();
        });

        $this->assertEquals(array('0-One', '1-Two', '2-Three'), $data);
    }

    public function testIteration()
    {
        $nodes = $this->filterXPath($this->loadFixture('list.html'), '//li');
        $nodeList = new NodeList($nodes);

        $this->assertContainsOnlyInstancesOf('DiDom\Element', iterator_to_array($nodeList), 'Iterating a NodeList gives DOMElement or DiDom\Element instances');
    }

    public function testSlice()
    {
        $nodes = $this->filterXPath($this->loadFixture('list.html'), '//ul[1]/li');
        $nodeList = new NodeList($nodes);

        $this->assertNotSame($nodeList->slice(), $nodeList, '->slice() returns a new instance of a NodeList');
        $this->assertInstanceOf('Tweezers\NodeList', $nodeList->slice(), '->slice() returns a new instance of a NodeList');

        $this->assertCount(3, $nodeList->slice(), '->slice() does not slice the nodes in the list if any param is entered');
        $this->assertCount(1, $nodeList->slice(1, 1), '->slice() slices the nodes in the list');
    }

    public function testReduce()
    {
        $nodes = $this->filterXPath($this->loadFixture('list.html'), '//ul[1]/li');
        $nodeList = new NodeList($nodes);

        $nodes = $nodeList->reduce(function ($node, $i) {
            return $i !== 1;
        });

        $this->assertNotSame($nodes, $nodeList, '->reduce() returns a new instance of a NodeList');
        $this->assertInstanceOf('Tweezers\NodeList', $nodes, '->reduce() returns a new instance of a NodeList');

        $this->assertCount(2, $nodes, '->reduce() filters the nodes in the list');
    }

    public function testExtract()
    {
        $nodeList = $this->filterXPath($this->loadFixture('list.html'), '//ul[1]/li');
        $nodeList = new NodeList($nodeList);

        $this->assertEquals(array('One', 'Two', 'Three'), $nodeList->extract('_text'), '->extract() returns an array of extracted data from the node list');
        $this->assertEquals(array(array('One', 'item'), array('Two', 'item'), array('Three', 'item')), $nodeList->extract(array('_text', 'class')), '->extract() returns an array of extracted data from the node list');

        $nodeList = new NodeList();

        $this->assertEquals(array(), $nodeList->extract('_text'), '->extract() returns an empty array if the node list is empty');
    }

    protected function createDomDocument($html = null)
    {
        $dom = new \DOMDocument();
        $html = $html ?: '<html><div class="foo"></div></html>';
        $dom->loadXML($html);

        return $dom;
    }

    protected function createArrayOfNodes($size = 1)
    {
        $nodes = array();

        foreach ($this->createNodeList($size) as $node) {
            $nodes[] = $node;
        }

        return $nodes;
    }

    protected function createNodeList($size = 1)
    {
        $html = sprintf('<html>%s</html>', str_repeat('<div class="foo"></div>', $size));
        $dom = new \DOMDocument();
        $dom->loadXML($html);
        $domxpath = new \DOMXPath($dom);

        return $domxpath->query('//div');
    }

    protected function filterXPath($html, $xpath)
    {
        $dom = new \DOMDocument();
        $dom->loadHTML($html);
        $domxpath = new \DOMXPath($dom);

        return $domxpath->query($xpath);
    }
}
