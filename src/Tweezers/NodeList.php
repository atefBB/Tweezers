<?php

namespace Tweezers;

class NodeList implements \Countable, \IteratorAggregate
{
    /**
     * @var \DOMDocument
     */
    protected $document;

    /**
     * @var \DOMElement[]
     */
    protected $nodes = array();

    /**
     * Constructor.
     *
     * @param Element|\DOMNodeList|\DOMElement|array|null $nodes An array of nodes
     */
    public function __construct($nodes = null)
    {
        $this->add($nodes);
    }

    /**
     * Removes all the nodes.
     */
    public function clear()
    {
        $this->nodes = array();
        $this->document = null;
    }

    /**
     * Adds a node to the current list of nodes.
     *
     * This method uses the appropriate specialized add*() method based
     * on the type of the argument.
     *
     * @param Element|\DOMNodeList|\DOMElement|array|null $node A node
     *
     * @throws \InvalidArgumentException When node is not the expected type.
     */
    public function add($node)
    {
        if ($node instanceof \DOMNodeList) {
            $this->addNodeList($node);
        } elseif ($node instanceof \DOMElement) {
            $this->addNode($node);
        } elseif ($node instanceof Element) {
            $this->addNode($node);
        } elseif (is_array($node)) {
            $this->addNodes($node);
        } elseif (null !== $node) {
            throw new \InvalidArgumentException(sprintf('Expecting a DOMNodeList or Tweezers\Element instance, an array, or null, but got "%s".', is_object($node) ? get_class($node) : gettype($node)));
        }
    }

    /**
     * Adds a \DOMNodeList to the list of nodes.
     *
     * @param \DOMNodeList $nodes A \DOMNodeList instance
     */
    public function addNodeList(\DOMNodeList $nodes)
    {
        foreach ($nodes as $node) {
            if ($node instanceof \DOMElement) {
                $this->addNode($node);
            }
        }
    }

    /**
     * Adds an array of \DOMNode instances to the list of nodes.
     *
     * @param Element[]|\DOMElement[] $nodes An array of \DOMNode instances
     */
    public function addNodes(array $nodes)
    {
        foreach ($nodes as $node) {
            $this->add($node);
        }
    }

    /**
     * Adds a \DOMNode instance to the list of nodes.
     *
     * @param Element|\DOMElement $node A \DOMElement or Element instance
     */
    public function addNode($node)
    {
        if ($node instanceof Element) {
            $node = $node->getNode();
        }

        if (!$node instanceof \DOMElement) {
            throw new \InvalidArgumentException(sprintf('Nodes set in a NodeList must be DOMElement or Tweezers\Element  instances, "%s" given.', get_class($node)));
        }

        if ($this->document !== null && $this->document !== $node->ownerDocument) {
            throw new \InvalidArgumentException('Attaching DOM nodes from multiple documents in the same NodeList is forbidden.');
        }

        if ($this->document === null) {
            $this->document = $node->ownerDocument;
        }

        // Don't add duplicate nodes in the NodeList
        if ($this->has($node)) {
            return;
        }

        $this->nodes[] = $node;
    }

    /**
     * @param int $position
     *
     * @return Element|null
     */
    public function getNode($position)
    {
        if (isset($this->nodes[$position])) {
            return new Element($this->nodes[$position]);
        }
    }

    /**
     * @param Element|\DOMElement $node A node
     *
     * @return bool
     */
    public function has($node)
    {
        if ($node instanceof Element) {
            $node = $node->getNode();
        }

        return in_array($node, $this->nodes, true);
    }

    /**
     * Returns array with all nodes.
     * 
     * @return Element[]
     */
    public function all()
    {
        return $this->toArray();
    }

    /**
     * Returns the first node of the current selection.
     *
     * @return Element
     */
    public function first()
    {
        return $this->getNode(0);
    }

    /**
     * Returns the last node of the current selection.
     *
     * @return Element
     */
    public function last()
    {
        return $this->getNode(count($this->nodes) - 1);
    }

    /**
     * Calls an anonymous function on each node of the list.
     *
     * @param \Closure $closure An anonymous function
     * @param mixed $userdata Will be passed as the third parameter to the callback
     *
     * @return array An array of values returned by the anonymous function
     */
    public function each(\Closure $closure, $userdata = null)
    {
        $data = array();

        foreach ($this->all() as $index => $node) {
            $data[] = $closure($node, $index, $userdata);
        }

        return $data;
    }

    /**
     * Slices the list of nodes by $offset and $length.
     *
     * @param int $offset
     * @param int $length
     *
     * @return NodeList A NodeList instance with the sliced nodes
     */
    public function slice($offset = 0, $length = null)
    {
        return new static(array_slice($this->nodes, $offset, $length));
    }

    /**
     * Reduces the list of nodes by calling an anonymous function.
     *
     * To remove a node from the list, the anonymous function must return false.
     *
     * @param \Closure $closure An anonymous function
     *
     * @return NodeList A NodeList instance with the selected nodes.
     */
    public function reduce(\Closure $closure)
    {
        $nodes = array();

        foreach ($this->all() as $index => $node) {
            if (false !== $closure($node, $index)) {
                $nodes[] = $node;
            }
        }

        return new static($nodes);
    }

    /**
     * Extracts information from the list of nodes.
     *
     * You can extract attributes or/and the node value (_text).
     *
     * @param array $attributes An array of attributes
     *
     * @return array An array of extracted values
     */
    public function extract($attributes)
    {
        $attributes = (array) $attributes;
        $count = count($attributes);
        $data = array();

        foreach ($this->nodes as $node) {
            $elements = array();

            foreach ($attributes as $attribute) {
                if ('_text' === $attribute) {
                    $elements[] = $node->nodeValue;
                } else {
                    $elements[] = $node->getAttribute($attribute);
                }
            }

            $data[] = $count > 1 ? $elements : $elements[0];
        }

        return $data;
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->nodes);
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return $this->count() == 0;
    }

    /**
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->toArray());
    }

    /**
     * Returns the array of nodes.
     * 
     * @return Element[]
     */
    public function toArray()
    {
        $nodes = [];

        foreach ($this->nodes as $node) {
            $nodes[] = new Element($node);
        }

        return $nodes;
    }

    /**
     * @param string $name The name of the method
     * @param array  $arguments An array of arguments
     * 
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        $allowedMethods = ['find', 'xpath', 'hasAttribute', 'getAttribute', 'attr', 'html', 'xml', 'text'];

        if (!in_array($name, $allowedMethods)) {
            throw new \BadMethodCallException(sprintf('Method [%s] does not exist', $name));
        }

        if ($this->count() === 0) {
            throw new \OutOfBoundsException('Collection is empty.');
        }

        return call_user_func_array([$this->first(), $name], $arguments);
    }
}
