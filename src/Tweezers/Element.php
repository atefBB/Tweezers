<?php

namespace Tweezers;

use DiDom\Element as DiDomElement;

class Element extends DiDomElement
{
    /**
     * Get the DOM document with the current element.
     * 
     * @return Crawler
     */
    public function toDocument($encoding = 'UTF-8')
    {
        $document = new Crawler(null, null, null);
        $document->appendChild($this->node);

        return $document;
    }
}