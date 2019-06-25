<?php

namespace NSCL\ToStr;

use NSCL\ToStr\Reflections\CallbackReflection;
use NSCL\ToStr\Reflections\ClosureReflection;
use NSCL\ToStr\Reflections\FunctionReflection;
use NSCL\ToStr\Reflections\ObjectReflection;

class Stringifier
{
    /**
     * @param mixed $var
     * @param string $type
     * @return string
     */
    public function stringify($var, $type)
    {
        $method = 'stringify_' . str_replace('-', '_', $type);

        if (method_exists($this, $method)) {
            return $this->$method($var);
        } else {
            return $this->stringify_undefined($var);
        }
    }

    /**
     * @param bool $value
     * @return string
     */
    public function stringify_bool($value)
    {
        return $value ? 'true' : 'false';
    }

    /**
     * @param bool $value
     * @return string
     */
    public function stringify_yesno($value)
    {
        return $value ? 'yes' : 'no';
    }

    /**
     * @param float $number
     * @return string
     */
    public function stringify_float($number)
    {
        // 14 - the approximate maximum number of decimal digits in PHP
        $string  = number_format($number, 14, '.', '');
        $decimal = strstr($string, '.');

        if ($decimal === false) {
            // The $number is integer and does not have a decimal part
            return $string;
        }

        $integer = strstr($string, '.', true); // Get string before "."
        $decimal = substr($decimal, 1); // Remove "."

        $radd = 0; // The number we need to add to the most right position

        if (regex_test('/9{4,}\d$/', $decimal)) {
            // Trim suffix like "557.05999999999995"
            $decimal = preg_replace('/9{4,}\d$/', '', $decimal);
            $radd = 1;
        } else {
            // Try to trim suffix like "557.07000000000005"
            $decimal = preg_replace('/0{4,}\d$/', '', $decimal);
        }

        // Trim ending zeros
        $decimal = rtrim($decimal, '0');

        // Build the final number
        $string = !empty($decimal) ? $integer . '.' . $decimal : $integer;

        if ($radd > 0) {
            $string = strradd($string, $radd);
        }

        return $string;
    }

    /**
     * @param string $string
     * @return string
     */
    public function stringify_string($string)
    {
        return '"' . $string . '"';
    }

    /**
     * @param array $array
     * @return string
     */
    public function stringify_array(array $array)
    {
        $itemsCount = count($array);

        if ($itemsCount > 0) {
            $description = $this->translate_plural('Array of n items', 'Array of %d item', 'Array of %d items', $itemsCount);
            $description = sprintf($description, $itemsCount);
        } else {
            $description = '';
        }

        return "[{$description}]";
    }

    /**
     * @param iterable $iterable
     * @param int $itemsCount Specify items count to prevent items recalculation.
     * @return string
     */
    public function stringify_iterable($iterable, $itemsCount = -1)
    {
        // count() will not work on iterable
        if ($itemsCount < 0) {
            $itemsCount = 0;

            foreach ($iterable as $_) {
                $itemsCount++;
            }
        }

        if ($itemsCount > 0) {
            $description = $this->translate_plural('Iterable with n items', 'Iterable with %d item', 'Iterable with %d items', $itemsCount);
            $description = sprintf($description, $itemsCount);
        } else {
            $description = '';
        }

        return "{{$description}}";
    }

    /**
     * @param \NSCL\ToStr\Reflections\ClosureReflection $closure
     * @return string
     */
    public function stringify_closure(ClosureReflection $closure)
    {
        $reflection = $closure->getReflection();
        $arguments  = $this->stringify_arguments($reflection['arguments']);

        return "function ({$arguments}) { ... }";
    }

    /**
     * @param \NSCL\ToStr\Reflections\FunctionReflection $function
     * @return string
     */
    public function stringify_function(FunctionReflection $function)
    {
        $reflection = $function->getReflection();
        $arguments  = $this->stringify_arguments($reflection['arguments']);
        $name       = $reflection['name'];

        return "function {$name}({$arguments}) { ... }";
    }

    /**
     * @param \NSCL\ToStr\Reflections\CallbackReflection $callback
     * @return string
     */
    public function stringify_callback(CallbackReflection $callback)
    {
        $reflection = $callback->getReflection();
        $arguments  = $this->stringify_arguments($reflection['arguments']);
        $name       = $reflection['name'];

        return "{$name}({$arguments}) { ... }";
    }

    /**
     * @param mixed $object The object to stringify.
     * @return string
     */
    public function stringify_object($object)
    {
        $description = $this->translate('Instance of a class', 'Instance of %s');
        $description = sprintf($description, get_class($object));

        return "{%{$description}%}";
    }

    /**
     * @param \NSCL\ToStr\Reflections\ObjectReflection|array $refobject Object
     *     reflection instance or it's data.
     * @param string $indent Optional. Indentation before each line with
     *     constant, property or method. Four spaces by default.
     * @return string
     */
    public function stringify_refobject($refobject, $indent = '    ')
    {
        $reflection = is_array($refobject) ? $refobject : $refobject->getReflection();

        $declaration = $this->stringify_declaration($reflection);
        $constants   = $this->stringify_constants($reflection['constants'], $indent);
        $properties  = $this->stringify_properties($reflection['properties'], $indent);
        $methods     = $this->stringify_methods($reflection['methods'], $indent);

        $beforeConstants  = '';
        $beforeProperties = (!empty($properties) && !empty($constants)) ? PHP_EOL : '';
        $beforeMethods    = (!empty($methods) && (!empty($properties) || !empty($constants))) ? PHP_EOL : '';

        $afterConstants  = !empty($constants)  ? PHP_EOL : '';
        $afterProperties = !empty($properties) ? PHP_EOL : '';
        $afterMethods    = !empty($methods)    ? PHP_EOL : '';

        $string  = $declaration . PHP_EOL;
        $string .= '{' . PHP_EOL;
        $string .= $beforeConstants  . $constants  . $afterConstants;
        $string .= $beforeProperties . $properties . $afterProperties;
        $string .= $beforeMethods    . $methods    . $afterMethods;
        $string .= '}';

        return $string;
    }

    /**
     * @param array $constants [name, value], where "value" - already
     *     stringified value.
     * @param string $indent Optional. Indentation before each line with
     *      constant. Four spaces by default.
     * @return string[]
     */
    protected function stringify_constants(array $constants, $indent = '    ')
    {
        $strings = array_map(function ($constant) use ($indent) {
            $name  = $constant['name'];
            $value = $constant['value'];

            return $indent . "const {$name} = {$value};";
        }, $constants);

        return implode(PHP_EOL, $strings);
    }

    /**
     * @param array $properties [visibility, name, value], where "value" -
     *     already stringified value.
     * @param string $indent Optional. Indentation before each line with
     *      property. Four spaces by default.
     * @return string[]
     */
    protected function stringify_properties(array $properties, $indent = '    ')
    {
        $strings = array_map(function ($property) use ($indent) {
            $visibility = $this->stringify_visibility($property['visibility']);
            $name       = $property['name'];
            $value      = $property['value'];

            // "final public static $x"
            $string = $indent . $visibility . ' $' . $name;

            // "final public static $x = 5"
            if (!is_null($value)) {
                $string .= ' = ' . $value;
            }

            // "final public static $x = 5;"
            $string .= ';';

            return $string;
        }, $properties);

        return implode(PHP_EOL, $strings);
    }

    /**
     * @param array $methods [visibility, name, arguments]
     * @param string $indent Optional. Indentation before each line with method.
     *     Four spaces by default.
     * @return string
     */
    protected function stringify_methods(array $methods, $indent = '    ')
    {
        $strings = array_map(function ($method) use ($indent) {
            $visibility = $this->stringify_visibility($method['visibility']);
            $name       = $method['name'];
            $arguments  = $this->stringify_arguments($method['arguments']);

            // "final public static function f($x[, $y]) { ... }"
            return $indent . $visibility . " function {$name}({$arguments}) { ... }";
        }, $methods);

        return implode(PHP_EOL, $strings);
    }

    /**
     * @param array $arguments [required => ["x"], optional => ["y"]]
     * @return string "$x[, $y]"
     */
    protected function stringify_arguments(array $arguments)
    {
        $required = $arguments['required'];
        $optional = $arguments['optional'];

        $string = '';

        // "$x"
        if (!empty($required)) {
            $string .= '$' . implode(', $', $required);
        }

        if (!empty($optional)) {
            // "$x["
            $string .= '[';

            // "$x[, "
            if (!empty($required)) {
                $string .= ', ';
            }

            // "$x[, $y"
            $string .= '$' . implode(', $', $optional);

            // "$x[, $y]"
            $string .= ']';
        }

        return $string;
    }

    /**
     * @param array $visibility [level, is-abstract, is-final, is-static]
     * @return string "final public static"
     */
    protected function stringify_visibility(array $visibility)
    {
        $level      = $visibility['level'];
        $isAbstract = $visibility['is-abstract'];
        $isFinal    = $visibility['is-final'];
        $isStatic   = $visibility['is-static'];

        $string = '';

        // "final "
        if ($isAbstract) {
            $string .= 'abstract ';
        } else if ($isFinal) {
            $string .= 'final ';
        }

        // "final public "
        $string .= $level . ' ';

        // "final public static "
        if ($isStatic) {
            $string .= 'static';
        }

        return rtrim($string); // Trim space after level or final/abstract
    }

    /**
     * Stringify class declaration.
     *
     * <i>Notice: the method is not intended to stringify the class. Only
     * reflections of the objects are allowed.</i>
     *
     * @param array $reflection [visibility, name, implements, constants,
     *                          properties, methods]
     * @return string "final class X implements Y, Z"
     */
    protected function stringify_declaration(array $reflection)
    {
        // "final class X"
        $string = $reflection['visibility']['is-final'] ? 'final class ' : 'class ';
        $string .= $reflection['name'];

        // "final class X implements Y, Z"
        if (!empty($reflection['implements'])) {
            $string .= ' implements ' . implode(', ', $reflection['implements']);
        }

        return $string;
    }

    /**
     * @param \NSCL\ToStr\Reflections\ObjectReflection|array $refstruct Object
     *     reflection instance or it's data.
     * @param string $indent Optional. Indentation before each line with
     *     constant, property or method. Four spaces by default.
     * @return string
     */
    public function stringify_refstruct($refstruct, $indent = '    ')
    {
        $reflection = is_array($refstruct) ? $refstruct : $refstruct->getReflection();

        // Remove all constants and methods, leave only properties
        $reflection['constants'] = array();
        $reflection['methods'] = array();

        return $this->stringify_refobject($reflection, $indent);
    }

    /**
     * @param \DateTime $date
     * @return string Date in format "j F, Y (H:i:s)": <i>"31 December, 2017 (18:32:59)"</i>.
     */
    public function stringify_date(\DateTime $date)
    {
        return $date->format('{j F, Y (H:i:s)}');
    }

    /**
     * @param mixed $_ Optional. Null by default.
     * @return string
     */
    public function stringify_null($_ = null)
    {
        return 'null';
    }

    /**
     * @param mixed $resource
     * @return string
     */
    public function stringify_resource($resource)
    {
        return $this->stringify_undefined($resource);
    }

    /**
     * @param mixed $var
     * @return string
     */
    public function stringify_undefined($var)
    {
        $string = print_r($var, true);
        $string = trim($string);
        $string = preg_replace('/\s+/', ' ', $string);

        return $string;
    }

    /**
     * @param mixed $var
     * @return string
     */
    public function stringify_asis($var)
    {
        return (string)$var;
    }

    /**
     * @param string $context
     * @param string $text
     * @return string
     */
    protected function translate($context, $text)
    {
        // No translation here, but you can override the method in your project
        // and add the real translation
        return $text;
    }

    /**
     * @param string $context
     * @param string $singular
     * @param string $plural
     * @param int $n
     * @return string
     */
    protected function translate_plural($context, $singular, $plural, $n)
    {
        // No translation here, but you can override the method in your project
        // and add the real translation
        if ($n == 1) {
            return $singular;
        } else {
            return $plural;
        }
    }
}
