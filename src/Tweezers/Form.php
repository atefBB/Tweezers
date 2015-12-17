<?php

namespace Tweezers;

use Tweezers\Field\FormFieldInterface;
use DiDom\Query;

class Form extends Link implements \ArrayAccess
{
    /**
     * @var FormFieldRegistry
     */
    private $fields;

    /**
     * @var string
     */
    private $baseHref;

    /**
     * @var string The method to use for the link
     */
    protected $method;

    /**
     * Constructor.
     *
     * @param \DOMElement $node       A \DOMElement instance
     * @param string      $currentUri The URI of the page where the form is embedded
     * @param string      $method     The method to use for the link (if null, it defaults to the method defined by the form)
     * @param string      $baseHref   The URI of the <base> used for relative links, but not for empty action
     *
     * @throws \LogicException When node type is incorrect
     */
    public function __construct(\DOMElement $node, $currentUri, $method = null, $baseHref = null)
    {
        parent::__construct($node, $currentUri);

        $method = $method ?: $this->getAttribute('method', 'GET');
        $this->method = strtoupper($method);

        $this->baseHref = $baseHref;
        $this->initialize();
    }

    /**
     * Sets the value of the fields.
     *
     * @param array $values An array of field values
     *
     * @return Form
     */
    public function setValues(array $values)
    {
        foreach ($values as $name => $value) {
            $this->fields->set($name, $value);
        }

        return $this;
    }

    /**
     * Sets the value of the fields.
     *
     * @param array $values An array of field values
     *
     * @return Form
     */
    public function fill(array $values)
    {
        return $this->setValues($values);
    }

    /**
     * Gets the field values.
     *
     * The returned array does not include file fields (@see getFiles).
     *
     * @return array An array of field values
     */
    public function getValues()
    {
        $values = array();

        foreach ($this->fields->all() as $name => $field) {
            if ($field->isDisabled()) {
                continue;
            }

            if ($field->hasValue() and !$field instanceof Field\FileFormField) {
                $values[$name] = $field->getValue();
            }
        }

        return $values;
    }

    /**
     * Gets the file field values.
     *
     * @return array An array of file field values
     */
    public function getFiles()
    {
        if (!in_array($this->getMethod(), array('POST', 'PUT', 'DELETE', 'PATCH'))) {
            return array();
        }

        $files = array();

        foreach ($this->fields->all() as $name => $field) {
            if ($field->isDisabled()) {
                continue;
            }

            if ($field instanceof Field\FileFormField) {
                $files[$name] = $field->getValue();
            }
        }

        return $files;
    }

    /**
     * Gets the field values as PHP.
     *
     * This method converts fields with the array notation
     * (like foo[bar] to arrays) like PHP does.
     *
     * @return array An array of field values
     */
    public function getPhpValues()
    {
        $values = array();

        foreach ($this->getValues() as $name => $value) {
            $query = http_build_query(array($name => $value), '', '&');

            if (!empty($query)) {
                parse_str($query, $expandedValue);

                $varName = substr($name, 0, strlen(key($expandedValue)));
                $values = array_replace_recursive($values, array($varName => current($expandedValue)));
            }
        }

        return $values;
    }

    /**
     * Gets the file field values as PHP.
     *
     * This method converts fields with the array notation
     * (like foo[bar] to arrays) like PHP does.
     *
     * @return array An array of field values
     */
    public function getPhpFiles()
    {
        $values = array();

        foreach ($this->getFiles() as $name => $value) {
            $query = http_build_query(array($name => $value), '', '&');

            if (!empty($query)) {
                parse_str($query, $expandedValue);

                $varName = substr($name, 0, strlen(key($expandedValue)));
                $values = array_replace_recursive($values, array($varName => current($expandedValue)));
            }
        }

        return $values;
    }

    /**
     * Gets the URI of the form.
     *
     * The returned URI is not the same as the form "action" attribute.
     * This method merges the value if the method is GET to mimics
     * browser behavior.
     *
     * @return string The URI
     */
    public function getUri()
    {
        $uri = parent::getUri();

        if (!in_array($this->getMethod(), array('POST', 'PUT', 'DELETE', 'PATCH'))) {
            $query = parse_url($uri, PHP_URL_QUERY);
            $currentParameters = array();

            if ($query) {
                parse_str($query, $currentParameters);
            }

            $queryString = http_build_query(array_merge($currentParameters, $this->getValues()), null, '&');

            $pos = strpos($uri, '?');
            $base = $pos === false ? $uri : substr($uri, 0, $pos);
            $uri = rtrim($base.'?'.$queryString, '?');
        }

        return $uri;
    }

    /**
     * Returns raw URI data.
     *
     * @return string
     */
    protected function getRawUri()
    {
        return $this->getAttribute('action');
    }

    /**
     * Gets the form method.
     *
     * If no method is defined in the form, GET is returned.
     *
     * @return string The method
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Adds a named field.
     *
     * @param FormFieldInterface $field The field
     */
    public function addField(FormFieldInterface $field)
    {
        $this->fields->add($field);

        return $this;
    }

    /**
     * Returns true if the named field exists.
     *
     * @param string $name The field name
     *
     * @return bool true if the field exists, false otherwise
     */
    public function hasField($name)
    {
        return $this->fields->has($name);
    }

    /**
     * Gets a named field.
     *
     * @param string $name The field name
     *
     * @return FormFieldInterface The field instance
     *
     * @throws \InvalidArgumentException When field is not present in this form
     */
    public function getField($name)
    {
        return $this->fields->get($name);
    }

    /**
     * Removes a field from the form.
     *
     * @param string $name The field name
     *
     * @throws \InvalidArgumentException when the name is malformed
     */
    public function removeField($name)
    {
        $this->fields->remove($name);
    }

    /**
     * Gets all fields.
     *
     * @return FormFieldInterface[] An array of fields
     */
    public function allFields()
    {
        return $this->fields->all();
    }

    /**
     * Returns true if the named field exists.
     *
     * @param string $name The field name
     *
     * @return bool true if the field exists, false otherwise
     */
    public function offsetExists($name)
    {
        return $this->fields->has($name);
    }

    /**
     * Gets the value of a field.
     *
     * @param string $name The field name
     *
     * @return FormFieldInterface The associated Field instance
     *
     * @throws \InvalidArgumentException if the field does not exist
     */
    public function offsetGet($name)
    {
        return $this->fields->get($name);
    }

    /**
     * Sets the value of a field.
     *
     * @param string       $name  The field name
     * @param string|array $value The value of the field
     *
     * @throws \InvalidArgumentException if the field does not exist
     */
    public function offsetSet($name, $value)
    {
        $this->fields->set($name, $value);
    }

    /**
     * Removes a field from the form.
     *
     * @param string $name The field name
     */
    public function offsetUnset($name)
    {
        $this->fields->remove($name);
    }

    /**
     * Disables validation.
     *
     * @return self
     */
    public function disableValidation()
    {
        foreach ($this->fields->all() as $field) {
            if ($field instanceof Field\SelectFormField or $field instanceof Field\RadioGroup) {
                $field->disableValidation();
            }
        }

        return $this;
    }

    /**
     * Sets the node for the form.
     *
     * Expects a 'submit' button \DOMElement and finds the corresponding form element, or the form element itself.
     *
     * @param \DOMElement $node A \DOMElement instance
     *
     * @throws \LogicException If given node is not a button or input or does not have a form ancestor
     */
    protected function setNode(\DOMElement $node)
    {
        if ($node->nodeName !== 'form') {
            throw new \LogicException(sprintf('Unable to submit on a "%s" tag.', $node->nodeName));
        }

        $this->node = $node;
    }

    /**
     * Adds form elements related to this form.
     *
     * Creates an internal copy of the submitted 'button' element and
     * the form node or the entire document depending on whether we need
     * to find non-descendant elements through HTML5 'form' attribute.
     *
     * @throws \LogicException When node type is incorrect
     */
    private function initialize()
    {
        if ($this->tag !== 'form') {
            throw new \LogicException(sprintf('A Form can only be created from a form tag (%s given).', $this->tag));
        }

        $this->fields = new FormFieldRegistry();

        $fieldNodes = $this->find('input, button, textarea, select', Query::TYPE_CSS, false);

        foreach ($fieldNodes as $node) {
            $this->createField($node);
        }

        if ($this->baseHref and '' !== $this->hasAttribute('action')) {
            $this->currentUri = $this->baseHref;
        }
    }

    /**
     * @param \DOMElement $node
     */
    private function createField(\DOMElement $node)
    {
        if (!$node->hasAttribute('name') || !$node->getAttribute('name')) {
            return;
        }

        $nodeName = $node->nodeName;

        if ('select' == $nodeName) {
            $this->addField(new Field\SelectFormField($node));
        } elseif ('input' == $nodeName and 'checkbox' == strtolower($node->getAttribute('type'))) {
            $this->addField(new Field\CheckBoxFormField($node));
        } elseif ('input' == $nodeName and 'radio' == strtolower($node->getAttribute('type'))) {
            // there may be other fields with the same name that are no choice
            if ($this->hasField($node->getAttribute('name')) and $this->getField($node->getAttribute('name')) instanceof Field\RadioGroup) {
                $this->getField($node->getAttribute('name'))->addChoice($node);
            } else {
                $radioGroup = new Field\RadioGroup($node->getAttribute('name'));
                $radioGroup->addChoice($node);

                $this->addField($radioGroup);
            }
        } elseif ('input' == $nodeName and 'file' == strtolower($node->getAttribute('type'))) {
            $this->addField(new Field\FileFormField($node));
        } elseif ('input' == $nodeName and !in_array(strtolower($node->getAttribute('type')), array('submit', 'button', 'image'))) {
            $this->addField(new Field\InputFormField($node));
        } elseif ('textarea' == $nodeName) {
            $this->addField(new Field\TextareaFormField($node));
        }
    }
}
