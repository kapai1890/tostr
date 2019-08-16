<?php

namespace NSCL\ToStr;

use NSCL\ToStr\Reflections\CallbackReflection;
use NSCL\ToStr\Reflections\ClosureReflection;
use NSCL\ToStr\Reflections\FunctionReflection;
use NSCL\ToStr\Reflections\ObjectReflection;

class StringBuilder
{
    /** @var \NSCL\ToStr\Stringifier */
    protected $stringifier = null;

    /** @var bool Build the max hierarchy of objects. */
    protected $isObjectsMode = false;

    /** @var string[] Updates on each call of buildStringWithObjects(). */
    protected $recursiveClasses = array();

    public function __construct(Stringifier $stringifier)
    {
        $this->stringifier = $stringifier;
    }

    /**
     * @param mixed[] $vars
     * @param int $maxDepth Optional. -1 by default (auto detect).
     * @return string
     */
    public function buildString(array $vars, $maxDepth = -1)
    {
        $strings = array();

        foreach ($vars as $var) {
            $type = typeof($var);
            $strings[] = $this->toString($var, $type, 1, $maxDepth);
        }

        return implode(' ', $strings);
    }

    /**
     * @param mixed $var
     * @param string $type
     * @param int $maxDepth Optional. -1 by default (auto detect).
     * @return string
     */
    public function buildStringAs($var, $type, $maxDepth = -1)
    {
        return $this->toString($var, $type, 1, $maxDepth);
    }

    /**
     * @param mixed $var Any object.
     * @param int $maxDepth Optional. -1 by default (auto detect).
     * @return string
     */
    public function buildStringWithObjects($var, $maxDepth = -1, $recursiveClasses = array())
    {
        // It's not really matter if the $var is not an object...
        $type = is_object($var) ? 'object' : typeof($var);

        $this->recursiveClasses = $this->defaultRecursiveClasses() + $recursiveClasses;

        $this->isObjectsMode = true;
        $string = $this->toString($var, $type, 1, $maxDepth);
        $this->isObjectsMode = false;

        return $string;
    }

    /**
     * @param mixed $var
     * @param string $type
     * @param int $depth
     * @param int $maxDepth
     * @param string[] $parents Stringified classes on upper layers.
     * @return string
     */
    protected function toString($var, $type, $depth, $maxDepth, array $parents = array())
    {
        if ($maxDepth < 0) {
            $maxDepth = $this->getMaxDepth();
        }

        // Convert some types of $var into Reflection
        switch ($type) {
            case 'closure':  $var = new ClosureReflection($var);  break;
            case 'function': $var = new FunctionReflection($var); break;
            case 'callback': $var = new CallbackReflection($var); break;
        }

        // Convert $var with inner items in special way (only if have more levels)
        if ($depth < $maxDepth) {
            switch ($type) {
                case 'array':     return $this->arrayToString($var, $depth, $maxDepth, $parents);    break;
                case 'iterable':  return $this->iterableToString($var, $depth, $maxDepth, $parents); break;
                case 'object':    return $this->objectToString($var, $type, $depth, $maxDepth, $parents); break;
                case 'structure': return $this->objectToString($var, $type, $depth, $maxDepth, $parents); break;
            }
        }

        // Otherwise or if no more levels left
        return $this->stringifier->stringify($var, $type);
    }

    protected function arrayToString(array $array, $depth, $maxDepth, array $parents)
    {
        $isHashmap = !is_numeric_natural_array($array);
        $strings = array();

        foreach ($array as $index => $value) {
            $type = typeof($value);
            $string = $this->toString($value, $type, $depth + 1, $maxDepth, $parents);

            if ($isHashmap) {
                $key = $this->stringifier->stringify($index, typeof($index));
                $strings[] = "{$key} => {$string}";
            } else {
                $strings[] = $string;
            }
        }

        return '[' . $this->concatenateItems($strings) . ']';
    }

    protected function iterableToString($values, $depth, $maxDepth, array $parents)
    {
        $strings = array();

        foreach ($values as $value) {
            $type = typeof($value);
            $strings[] = $this->toString($value, $type, $depth + 1, $maxDepth, $parents);
        }

        return '{' . $this->concatenateItems($strings) . '}';
    }

    protected function objectToString($object, $type, $depth, $maxDepth, array $parents)
    {
        $currentClass = get_class($object);

        // Return dummy text if can't go deeper
        if (!$this->canGoDeeper($currentClass, $parents)) {
            return $this->stringifier->stringify_object($object);
        }

        // Reflect the object
        $reflectionObject = new ObjectReflection($object);
        $reflection = $reflectionObject->getReflection();

        // Update parents before converting the children
        $parents[] = $currentClass;

        // Stringify constants and properties before passing them to Stringifier
        $reflection['properties'] = $this->stringifyValues($reflection['properties'], $depth, $maxDepth, $parents);

        if ($type == 'object') {
            $reflection['constants'] = $this->stringifyValues($reflection['constants'], $depth, $maxDepth, $parents, true);
        }

        if ($type == 'object') {
            return $this->stringifier->stringify_refobject($reflection, $this->getIndent());
        } else {
            return $this->stringifier->stringify_refstruct($reflection, $this->getIndent());
        }
    }

    protected function stringifyValues($children, $depth, $maxDepth, array $parents, $stringifyNull = false)
    {
        foreach ($children as &$child) {
            $value = $child['value'];
            $type = typeof($value);

            // Stringify inner multi-element components
            if (in_array($type, array('array', 'iterable', 'object'))) {
                $string = $this->toString($value, $type, $depth + 1, $maxDepth, $parents);
                $string = $this->increaseIndent($string);
                $child['value'] = $string;
            } else if ($type == 'string' || ($type == 'null' && $stringifyNull)) {
                // Wrap strings with "" and stringify nulls in constants
                $child['value'] = $this->stringifier->stringify($value, $type);
            } else {
                $child['value'] = $this->stringifier->stringify($value, $type);
            }
        }

        unset($child);

        return $children;
    }

    /**
     * Concatenate all values of the array or iterable object into one-line or
     * multi-line string.
     *
     * @param array $strings
     * @return string
     */
    protected function concatenateItems($strings)
    {
        $result = implode(', ', $strings);

        // ~ 120 (the soft limit in programming) minus brackets, minus some indent
        if (strlen($result) > 100) {
            $indent = $this->getIndent();

            // Increase indents in existing output
            $strings = array_map(array($this, 'increaseIndent'), $strings);

            // Place each value on new line
            $result = PHP_EOL . $indent;
            $result .= implode(',' . PHP_EOL . $indent, $strings);
            $result .= PHP_EOL;
        }

        return $result;
    }

    /**
     * Increase indents in existing output (objects output, for example).
     *
     * @param string $value
     * @return string
     */
    public function increaseIndent($value)
    {
        return preg_replace('/\n([^\n]*)/', "\n" . $this->getIndent() . '$1', $value);
    }

    protected function getIndent()
    {
        return '    ';
    }

    protected function getMaxDepth()
    {
        return 5;
    }

    /**
     * @return \NSCL\ToStr\Stringifier
     */
    public function getStringifier()
    {
        return $this->stringifier;
    }

    /**
     * @param \NSCL\ToStr\Stringifier $stringifier
     * @return self
     */
    public function setStringifier(Stringifier $stringifier)
    {
        $this->stringifier = $stringifier;
        return $this;
    }

    /**
     * @param string $currentClass
     * @param string[] $parents Stringified classes on upper layers.
     * @return bool
     */
    protected function canGoDeeper($currentClass, array $parents)
    {
        if ($this->isObjectsMode) {
            return !in_array($currentClass, $parents) || in_array($currentClass, $this->recursiveClasses);
        } else {
            return empty($parents);
        }
    }

    protected function defaultRecursiveClasses()
    {
        return array('stdClass');
    }
}
