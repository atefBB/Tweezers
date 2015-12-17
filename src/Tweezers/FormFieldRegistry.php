<?php

namespace Tweezers;

use Tweezers\Field\FormFieldInterface;

/**
 * This is an internal class that must not be used directly.
 */
class FormFieldRegistry
{
    private $fields = array();

    private $base;

    /**
     * Adds a field to the registry.
     *
     * @param FormFieldInterface $field The field
     *
     * @throws \InvalidArgumentException when the name is malformed
     */
    public function add(FormFieldInterface $field)
    {
        $segments = $this->getSegments($field->getName());
        $target = &$this->fields;

        while (count($segments) > 0) {
            if (!is_array($target)) {
                $target = array();
            }

            $path = array_shift($segments);

            if ($path === '') {
                $target = &$target[];
            } else {
                $target = &$target[$path];
            }
        }

        $target = $field;
    }

    /**
     * Removes a field and its children from the registry.
     *
     * @param string $name The fully qualified name of the base field
     *
     * @throws \InvalidArgumentException when the name is malformed
     */
    public function remove($name)
    {
        $segments = $this->getSegments($name);
        $target = &$this->fields;

        while (count($segments) > 1) {
            $path = array_shift($segments);

            if (!array_key_exists($path, $target)) {
                return;
            }

            $target = &$target[$path];
        }

        unset($target[array_shift($segments)]);
    }

    /**
     * Returns the value of the field and its children.
     *
     * @param string $name The fully qualified name of the field
     *
     * @return mixed The value of the field
     */
    public function &get($name)
    {
        $segments = $this->getSegments($name);
        $target = &$this->fields;

        while ($segments) {
            $path = array_shift($segments);

            if (!array_key_exists($path, $target)) {
                throw new \InvalidArgumentException(sprintf('Unreachable field "%s"', $path));
            }

            $target = &$target[$path];
        }

        return $target;
    }

    /**
     * Tests whether the form has the given field.
     *
     * @param string $name The fully qualified name of the field
     *
     * @return bool Whether the form has the given field
     */
    public function has($name)
    {
        try {
            $this->get($name);

            return true;
        } catch (\InvalidArgumentException $e) {
            return false;
        }
    }

    /**
     * Set the value of a field and its children.
     *
     * @param string $name  The fully qualified name of the field
     * @param mixed  $value The value
     *
     * @throws \InvalidArgumentException when the name is malformed
     * @throws \InvalidArgumentException if the field does not exist
     */
    public function set($name, $value)
    {
        $target = &$this->get($name);

        if ((!is_array($value) and $target instanceof FormFieldInterface) || $target instanceof Field\SelectFormField) {
            $target->setValue($value);
        } elseif (is_array($value)) {
            $fields = self::create($name, $value);

            foreach ($fields->all() as $key => $value) {
                $this->set($key, $value);
            }
        } else {
            throw new \InvalidArgumentException(sprintf('Cannot set value on a compound field "%s".', $name));
        }
    }

    /**
     * Returns the list of field with their value.
     *
     * @return FormField[] The list of fields as array((string) Fully qualified name => (mixed) value)
     */
    public function all()
    {
        return $this->walk($this->fields, $this->base);
    }

    /**
     * Transforms a PHP array in a list of fully qualified name / value.
     *
     * @param array  $array  The PHP array
     * @param string $base   The name of the base field
     * @param array  $output The initial values
     *
     * @return array The list of fields as array((string) Fully qualified name => (mixed) value)
     */
    private function walk(array $array, $base = '', array &$output = array())
    {
        foreach ($array as $key => $value) {
            $path = empty($base) ? $key : sprintf('%s[%s]', $base, $key);

            if (is_array($value)) {
                $this->walk($value, $path, $output);
            } else {
                $output[$path] = $value;
            }
        }

        return $output;
    }

    /**
     * Creates an instance of the class.
     *
     * This function is made private because it allows overriding the $base and
     * the $values properties without any type checking.
     *
     * @param string $base   The fully qualified name of the base field
     * @param array  $values The values of the fields
     *
     * @return FormFieldRegistry
     */
    private static function create($base, array $values)
    {
        $registry = new static();
        $registry->base = $base;
        $registry->fields = $values;

        return $registry;
    }

    /**
     * Splits a field name into segments as a web browser would do.
     *
     * <code>
     *     getSegments('base[foo][3][]') = array('base', 'foo, '3', '');
     * </code>
     *
     * @param string $name The name of the field
     *
     * @return string[] The list of segments
     *
     * @throws \InvalidArgumentException when the name is malformed
     */
    private function getSegments($name)
    {
        if (preg_match('/^(?P<base>[^[]+)(?P<extra>(\[.*)|$)/', $name, $matches)) {
            $segments = array($matches['base']);

            while (!empty($matches['extra'])) {
                if (preg_match('/^\[(?P<segment>.*?)\](?P<extra>.*)$/', $matches['extra'], $matches)) {
                    $segments[] = $matches['segment'];
                } else {
                    throw new \InvalidArgumentException(sprintf('Malformed field path "%s"', $name));
                }
            }

            return $segments;
        }

        throw new \InvalidArgumentException(sprintf('Malformed field path "%s"', $name));
    }
}
