<?php

namespace tostr;

class MessageBuilder
{
    /** @var \tostr\Reflector */
    protected $reflector;

    /** @var \tostr\Stringifier */
    protected $stringifier;

    /** @var string */
    protected $indent;

    public function __construct(Reflector $reflector, Stringifier $stringifier, $indent = '    ')
    {
        $this->reflector = $reflector;
        $this->stringifier = $stringifier;
        $this->indent = $indent;
    }

    /**
     * @param mixed[] $vars
     * @param int $maxDepth
     * @return string
     */
    public function buildMessage(array $vars, $maxDepth)
    {
        $strings = [];

        foreach ($vars as $var) {
            $type = typeof($var);
            $strings[] = $this->varToString($var, $type, 1, $maxDepth);
        }

        $message = implode(' ', $strings) . PHP_EOL;

        return $message;
    }

    /**
     * @param mixed $var
     * @param string $type
     * @param int $maxDepth
     * @return string
     */
    public function buildAs($var, $type, $maxDepth)
    {
        return $this->varToString($var, $type, 1, $maxDepth);
    }

    /**
     * @param mixed $var
     * @param string $type
     * @param int $depth
     * @param int $maxDepth
     * @param string[] $parents Stringified classes on upper layers.
     * @return string
     */
    protected function varToString($var, $type, $depth, $maxDepth, $parents = [])
    {
        if ($depth < $maxDepth) {
            if ($type == 'array') {
                return $this->arrayToString($var, $depth, $maxDepth, $parents);
            } else if ($type == 'iterable') {
                return $this->iterableToString($var, $depth, $maxDepth, $parents);
            } else if ($type == 'object' || $type == 'structure') {
                return $this->objectToString($var, $type, $depth, $maxDepth, $parents);
            }
        }

        // Max depth or type is not array|iterable|object
        return $this->stringifier->stringifyAs($var, $type);
    }

    /**
     * Stringify all the items in the array.
     *
     * @param array $values
     * @param int $depth
     * @param int $maxDepth
     * @param string[] $parents Pass information about parent classes to next layers.
     * @return string
     */
    protected function arrayToString(array $values, $depth, $maxDepth, $parents)
    {
        $strings   = [];
        $isHashmap = !is_numeric_natural_array($values);

        foreach ($values as $index => $value) {
            $type   = typeof($value);
            $string = $this->varToString($value, $type, $depth + 1, $maxDepth, $parents);

            if ($isHashmap) {
                // Also stringify key/index
                $string = $this->stringifier->stringify($index) . ' => ' . $string;
            }

            $strings[] = $string;
        }

        $result = $this->concatenateStrings($strings);

        return '[' . $result . ']';
    }

    /**
     * Stringify all the items in the iterable object.
     *
     * @param mixed $values
     * @param int $depth
     * @param int $maxDepth
     * @param string[] $parents Pass information about parent classes to next layers.
     * @return string
     */
    protected function iterableToString($values, $depth, $maxDepth, $parents)
    {
        $strings = [];

        foreach ($values as $value) {
            $type = typeof($value);
            $strings[] = $this->varToString($value, $type, $depth + 1, $maxDepth, $parents);
        }

        $result = $this->concatenateStrings($strings);

        return '{' . $results . '}';
    }

    /**
     * Build object string with all its constants, properties and methods.
     *
     * @param mixed $object
     * @param string $type
     * @param int $depth
     * @param int $maxDepth
     * @param string[] $parents Stringified classes on upper layers.
     * @return string
     */
    protected function objectToString($object, $type, $depth, $maxDepth, $parents)
    {
        $currentClass = get_class($object);
        $canGoDeeper = $this->canGoDeeper($currentClass, $depth, $maxDepth, $parents);

        // Return "{%Instance of CLASS_NAME%}" if can't go deeper
        if (!$canGoDeeper) {
            return $this->stringifier->stringifyObject($object);
        }

        // Reflect the object and convert it into a string with all it's fields
        // and methods
        $reflection = $this->reflector->reflectObject($object);

        // Update parents before converting the children
        $parents[] = $currentClass;

        // If we have 2+ more levels, then stringify the values of the constants
        // and properties properly
        if ($depth <= ($maxDepth - 2)) {
            $this->childrenToString($reflection['constants'], $depth, $maxDepth, $parents);
            $this->childrenToString($reflection['properties'], $depth, $maxDepth, $parents);
        }

        if ($type == 'object') {
            return $this->stringifier->stringifyRefobject($reflection);
        } else {
            return $this->stringifier->stringifyRefstruct($reflection);
        }
    }

    /**
     * Convert nested arrays/iterables/objects.
     *
     * @param array $children
     * @param int $depth
     * @param int $maxDepth
     * @param string[] $parents Stringified classes on upper layers.
     */
    protected function childrenToString(&$children, $depth, $maxDepth, $parents)
    {
        foreach ($children as &$child) {
            $type = typeof($child['value']);

            if (in_array($type, ['array', 'iterable', 'object', 'structure'])) {
                $stringValue = $this->varToString($child['value'], $type, $depth + 1, $maxDepth, $parents);
                $stringValue = $this->increaseIndent($stringValue);

                $child['value'] = new AsIs($stringValue);
            }
        }

        unset($child);
    }

    /**
     * @param string $currentClass
     * @param int $depth
     * @param int $maxDepth
     * @param string[] $parents Stringified classes on upper layers.
     * @return bool
     */
    protected function canGoDeeper($currentClass, $depth, $maxDepth, $parents)
    {
        // Go inside the array, not the objects (render only "top" object)
        return empty($parents);
    }

    /**
     * Concatenate all values of the array/iterable into one-line or multiline
     * string.
     *
     * @param array $strings
     * @return string
     */
    protected function concatenateStrings($strings)
    {
        $result = implode(', ', $strings);

        if (strlen($result) > 100) { // ~ 120 (the soft limit in programming) - brackets - some indent
            // Increase indents in multiline elements
            $strings = array_map([$this, 'increaseIndent'], $strings);

            // Place each value on new line
            $result = PHP_EOL . $this->indent;
            $result .= implode(',' . PHP_EOL . $this->indent, $strings);
            $result .= PHP_EOL;
        }

        return $result;
    }

    /**
     * Increase indents in multiline elements (like objects).
     *
     * @param string $value
     * @return string
     */
    public function increaseIndent($value)
    {
        return preg_replace('/\n([^\n]*)/', "\n" . $this->indent . '$1', $value);
    }
}
