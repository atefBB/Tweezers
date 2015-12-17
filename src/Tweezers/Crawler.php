<?php

namespace Tweezers;

use DiDom\Document;
use DiDom\Query;

class Crawler extends Document
{
    /**
     * @var string The current URI
     */
    protected $uri;

    /**
     * @var string The base href value
     */
    private $baseHref;

    /**
     * Constructor.
     *
     * @param mixed  $html
     * @param string $currentUri The current URI
     * @param string $baseHref   The base href value
     */
    public function __construct($html = null, $currentUri = null, $baseHref = null)
    {
        $this->uri = $currentUri;
        $this->baseHref = $baseHref ?: $currentUri;

        parent::__construct($html);
    }

    /**
     * Searches for the element in the DOM tree.
     * 
     * @param string $expression XPath expression or CSS selector
     * @param string $type       the type of the expression
     * @param string $wrapList
     *
     * @return NodeList|DiDom\Element[]
     */
    public function find($expression, $type = Query::TYPE_CSS, $wrapList = true)
    {
        $nodes = parent::find($expression, $type, false);

        return $wrapList ? new NodeList($nodes) : $nodes;
    }

    /**
     * Returns a Link object for the first node in the list.
     *
     * @param string $selector CSS selector
     *
     * @return Link A Link instance
     */
    public function link($selector = null)
    {
        $rules = ['tag' => 'a'];

        $node = $this->findByRule($selector, $rules)->first();

        return new Link($node->getNode(), $this->baseHref);
    }

    /**
     * Returns an array of Link objects for the nodes in the list.
     *
     * @param string $selector CSS selector
     *
     * @return Link[] An array of Link instances
     */
    public function links($selector = null)
    {
        $rules = ['tag' => 'a'];

        $links = array();
        $nodes = $this->findByRule($selector, $rules)->all();

        foreach ($nodes as $node) {
            $links[] = new Link($node->getNode(), $this->baseHref);
        }

        return $links;
    }

    /**
     * Returns a Form object for the first node in the list.
     *
     * @param string $selector CSS selector
     * @param string $method   The method for the form
     *
     * @return Form A Form instance
     */
    public function form($selector = null, $method = null)
    {
        $rules = ['tag' => 'form'];

        $node = $this->findByRule($selector, $rules)->first();

        return new Form($node->getNode(), $this->uri, $method, $this->baseHref);
    }

    /**
     * @param string CSS selector
     * @param array
     *
     * @return NodeList
     */
    public function findByRule($selector, $rules, $wrapElement = true)
    {
        $segments = $selector !== null ? Query::getSegments($selector) : [];

        $xpath = Query::buildXpath(array_merge($segments, $rules));

        return $this->xpath($xpath, $wrapElement);
    }
}
