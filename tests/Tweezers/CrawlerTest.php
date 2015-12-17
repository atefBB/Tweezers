<?php

namespace Tests\Crawler;

use Tests\TestCase;
use Tweezers\Crawler;
use DiDom\Query;

class CrawlerTest extends TestCase
{
    /**
     * @dataProvider findTests
     */
    public function testFind($html, $selector, $type, $count)
    {
        $crawler = new Crawler($html);
        $nodeList = $crawler->find($selector, $type);

        $this->assertInstanceOf('Tweezers\NodeList', $nodeList);
        $this->assertCount($count, $nodeList);
    }

    /**
     * @dataProvider linkTests
     */
    public function testLink($selector)
    {
        $html = $this->loadFixture('links.html');
        $crawler = new Crawler($html, 'http://www.example.com');

        $this->assertInstanceOf('Tweezers\Link', $crawler->link($selector));
    }

    /**
     * @dataProvider linkTests
     */
    public function testLinks($selector, $count)
    {
        $html = $this->loadFixture('links.html');
        $crawler = new Crawler($html, 'http://www.example.com');

        $links = $crawler->links($selector);

        $this->assertTrue(is_array($links));
        $this->assertCount($count, $links);

        foreach ($links as $link) {
            $this->assertInstanceOf('Tweezers\Link', $link);
        }
    }

    public function testFindByRule()
    {
        $html = $this->loadFixture('links.html');
        $crawler = new Crawler($html, 'http://www.example.com');

        $rules = [
            'tag' => 'a',
        ];

        $nodeList = $crawler->findByRule('div[href]', $rules);

        $this->assertInstanceOf('Tweezers\NodeList', $nodeList);
        $this->assertCount(3, $nodeList);
    }

    public function testForm()
    {
        $html = $this->loadFixture('form.html');
        $crawler = new Crawler($html, 'http://www.example.com');

        $this->assertInstanceOf('Tweezers\Form', $crawler->form());
    }

    public function findTests()
    {
        $html = $this->loadFixture('posts.html');

        return array(
            array($html, '.post h2', Query::TYPE_CSS, 3),
            array($html, '.fake h2', Query::TYPE_CSS, 0),
            array($html, '.post h2, .post p', Query::TYPE_CSS, 6),
            array($html, "//*[contains(concat(' ', normalize-space(@class), ' '), ' post ')]", Query::TYPE_XPATH, 3),
        );
    }

    public function linkTests()
    {
        return array(
            array(null, 3),
            array('.foo',1),
            array('a.foo', 1),
            array('[href=/bar]', 1),
            array('a[href=/bar]', 1),
        );
    }
}
