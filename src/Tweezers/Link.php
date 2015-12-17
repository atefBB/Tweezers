<?php

namespace Tweezers;

use DiDom\Element;

class Link extends Element
{
    /**
     * @var string The URI of the page where the link is embedded (or the base href)
     */
    protected $currentUri;

    /**
     * Constructor.
     *
     * @param  \DOMElement $node       A \DOMElement instance
     * @param  string      $currentUri The URI of the page where the link is embedded (or the base href)
     *
     * @throws \LogicException If given node is not an anchor
     */
    public function __construct(\DOMElement $node, $currentUri)
    {
        $this->setNode($node);
        $this->setCurrentUri($currentUri);
    }

    /**
     * Gets the URI associated with this link.
     *
     * @return string The URI
     */
    public function getUri()
    {
        $uri = trim($this->getRawUri());

        // absolute URL?
        if (null !== parse_url($uri, PHP_URL_SCHEME)) {
            return $uri;
        }

        // empty URI
        if (!$uri) {
            return $this->currentUri;
        }

        // an anchor
        if ('#' === $uri[0]) {
            return $this->cleanupAnchor($this->currentUri).$uri;
        }

        $baseUri = $this->cleanupUri($this->currentUri);

        if ('?' === $uri[0]) {
            return $baseUri.$uri;
        }

        // absolute URL with relative schema
        if (0 === strpos($uri, '//')) {
            return preg_replace('#^([^/]*)//.*$#', '$1', $baseUri).$uri;
        }

        $baseUri = preg_replace('#^(.*?//[^/]*)(?:\/.*)?$#', '$1', $baseUri);

        // absolute path
        if ('/' === $uri[0]) {
            return $baseUri.$uri;
        }

        // relative path
        $path = parse_url(substr($this->currentUri, strlen($baseUri)), PHP_URL_PATH);
        $path = $this->canonicalizePath(substr($path, 0, strrpos($path, '/')).'/'.$uri);
        $path = ltrim($path, '/');

        return $baseUri.'/'.$path;
    }

    /**
     * Returns raw URI data.
     *
     * @return string
     */
    protected function getRawUri()
    {
        return $this->getAttribute('href');
    }

    /**
     * Returns the canonicalized URI path (see RFC 3986, section 5.2.4).
     *
     * @param  string $path URI path
     *
     * @return string
     */
    protected function canonicalizePath($path)
    {
        if ('' === $path || '/' === $path) {
            return $path;
        }

        if ('.' === substr($path, -1)) {
            $path .= '/';
        }

        $output = array();

        foreach (explode('/', $path) as $segment) {
            if ('..' === $segment) {
                array_pop($output);
            } elseif ('.' !== $segment) {
                $output[] = $segment;
            }
        }

        return implode('/', $output);
    }

    /**
     * Sets current \DOMElement instance.
     *
     * @param  string $currentUri
     *
     * @throws \InvalidArgumentException if the node is not a link
     */
    protected function setCurrentUri($currentUri)
    {
        if ($currentUri !== '' and !in_array(strtolower(substr($currentUri, 0, 4)), array('http', 'file'))) {
            throw new \InvalidArgumentException(sprintf('Current URI must be an absolute URL ("%s").', $currentUri));
        }

        $this->currentUri = $currentUri;
    }

    /**
     * Removes the query string and the anchor from the given uri.
     *
     * @param string $uri The uri to clean
     *
     * @return string
     */
    private function cleanupUri($uri)
    {
        return $this->cleanupQuery($this->cleanupAnchor($uri));
    }

    /**
     * Remove the query string from the uri.
     *
     * @param string $uri
     *
     * @return string
     */
    private function cleanupQuery($uri)
    {
        if (false !== $pos = strpos($uri, '?')) {
            return substr($uri, 0, $pos);
        }

        return $uri;
    }

    /**
     * Remove the anchor from the uri.
     *
     * @param string $uri
     *
     * @return string
     */
    private function cleanupAnchor($uri)
    {
        if (false !== $pos = strpos($uri, '#')) {
            return substr($uri, 0, $pos);
        }

        return $uri;
    }

    /**
     * Sets current \DOMElement instance.
     *
     * @param \DOMElement $node A \DOMElement instance
     *
     * @throws \LogicException If given node is not an anchor
     */
    protected function setNode(\DOMElement $node)
    {
        if ('a' !== $node->nodeName and 'area' !== $node->nodeName and 'link' !== $node->nodeName) {
            throw new \LogicException(sprintf('Unable to navigate from a "%s" tag.', $node->nodeName));
        }

        parent::setNode($node);
    }
}
