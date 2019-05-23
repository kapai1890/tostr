<?php

namespace tostr;

class Stringifier
{
    /** @var \tostr\Reflector */
    protected $reflector;

    /** @var string */
    protected $indent;

    public function __construct(Reflector $reflector, $indent = '    ')
    {
        $this->reflector = $reflector;
        $this->indent = $indent;
    }

    /**
     * @param mixed $var
     * @return string
     */
    public function stringify($var)
    {
        $type = typeof($var);
        return $this->stringifyAs($var, $type);
    }

    /**
     * @param mixed $var
     * @param string $type
     * @return string
     */
    public function stringifyAs($var, $type)
    {
        $method = 'stringify' . ucfirst($type);

        if (method_exists($this, $method)) {
            return $this->$method($var);
        } else {
            return $this->stringifyUndefined($var);
        }
    }

    /**
     * @param bool $value
     * @return string
     */
    public function stringifyBool($value)
    {
        return $value ? 'true' : 'false';
    }

    /**
     * @param bool $value
     * @return string
     */
    public function stringifyYesno($value)
    {
        return $value ? 'yes' : 'no';
    }

    /**
     * @param int $number
     * @return string
     */
    public function stringifyInt($number)
    {
        return (string)$number;
    }

    /**
     * @param float $number
     * @return string
     */
    public function stringifyFloat($number)
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
    public function stringifyString($string)
    {
        return '"' . $string . '"';
    }

    /**
     * @param array $array
     * @return string
     */
    public function stringifyArray(array $array)
    {
        $itemsCount = count($array);

        if ($itemsCount > 0) {
            $description = $this->translatePlural('Array of n items', 'Array of %d item', 'Array of %d items', $itemsCount);
            $description = sprintf($description, $itemsCount);
        } else {
            $description = '';
        }

        return "[{$description}]";
    }

    public function stringifyIterable($iterable, $itemsCount = -1)
    {
        if ($itemsCount < 0) {
            $itemsCount = 0;

            foreach ($iterable as $_) {
                $itemsCount++;
            }
        }

        if ($itemsCount > 0) {
            $description = $this->translatePlural('Iterable with n items', 'Iterable with %d item', 'Iterable with %d items', $itemsCount);
            $description = sprintf($description, $itemsCount);
        } else {
            $description = '';
        }

        return "{{$description}}";
    }

    /**
     * @param array $arguments [required => ["x"], optional => ["y"]]
     * @return string "$x[, $y]"
     */
    protected function stringifyArguments(array $arguments)
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
     * @param \Closure $closure
     * @return string
     */
    public function stringifyClosure(\Closure $closure)
    {
        $reflection = $this->reflector->reflectClosure($closure);
        $arguments  = $this->stringifyArguments($reflection['arguments']);

        return "function ({$arguments}) { ... }";
    }

    /**
     * @param string $functionName
     * @return string
     */
    public function stringifyFunction($functionName)
    {
        $reflection = $this->reflector->reflectFunction($functionName);
        $arguments  = $this->stringifyArguments($reflection['arguments']);
        $name       = $reflection['name'];

        return "function {$name}({$arguments}) { ... }";
    }

    /**
     * @param array $callback An array like [%class or object%, %method name%].
     * @return string
     */
    public function stringifyCallback(array $callback)
    {
        $reflection = $this->reflector->reflectCallback($callback);
        $arguments  = $this->stringifyArguments($reflection['arguments']);
        $name       = $reflection['name'];

        return "{$name}({$arguments}) { ... }";
    }

    /**
     * @param array $visibility [level, is-abstract, is-final, is-static]
     * @return string "final public static"
     */
    protected function stringifyVisibility(array $visibility)
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
     * Notice: the method is not intended to stringify the class. Only
     * reflections of the objects are allowed.
     *
     * @param array $reflection [visibility, name, implements, constants,
     *                          properties, methods]
     * @return string "final class X implements Y, Z"
     */
    protected function stringifyDeclaration(array $reflection)
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
     * @param array $constants [name, value]
     * @param bool $addIndents Optional. Add indent to each line with constant.
     *                         True by default.
     * @return string[]
     */
    public function stringifyConstants(array $constants, $addIndent = true)
    {
        $strings = array_map(function ($constant) use ($addIndent) {
            $name  = $constant['name'];
            $value = $this->stringify($constant['value']);

            $string = $addIndent ? $this->indent : '';
            $string .= "const {$name} = {$value};";

            return $string;
        }, $constants);

        return implode(PHP_EOL, $strings);
    }

    /**
     * @param array $properties [visibility, name, value]
     * @param bool $addIndents Optional. Add indent to each line with constant.
     *                         True by default.
     * @return string[]
     */
    public function stringifyProperties(array $properties, $addIndent = true)
    {
        $strings = array_map(function ($property) use ($addIndent) {
            $visibility = $this->stringifyVisibility($property['visibility']);
            $name       = $property['name'];
            $value      = $property['value'];

            $string = $addIndent ? $this->indent : '';

            // "final public static $x"
            $string .= $visibility . ' $' . $name;

            // "final public static $x = 5"
            if (!is_null($value)) {
                $string .= ' = ' . $this->stringify($value);
            }

            // "final public static $x = 5;"
            $string .= ';';

            return $string;
        }, $properties);

        return implode(PHP_EOL, $strings);
    }

    /**
     * @param array $methods [visibility, name, arguments]
     * @param bool $addIndents Optional. Add indent to each line with constant.
     *                         True by default.
     * @return string
     */
    public function stringifyMethods(array $methods, $addIndent = true)
    {
        $strings = array_map(function ($method) use ($addIndent) {
            $visibility = $this->stringifyVisibility($method['visibility']);
            $name       = $method['name'];
            $arguments  = $this->stringifyArguments($method['arguments']);

            $string = $addIndent ? $this->indent : '';
            // "final public static function f"
            $string .= "{$visibility} function {$name}";
            // "final public static function f($x[, $y]) { ... }"
            $string .= "({$arguments}) { ... }";

            return $string;
        }, $methods);

        return implode(PHP_EOL, $strings);
    }

    /**
     * @param mixed $object The object to stringify.
     * @return string
     */
    public function stringifyObject($object)
    {
        $description = $this->translate('Instance of a class', 'Instance of %s');
        $description = sprintf($description, get_class($object));

        return "{%{$description}%}";
    }

    /**
     * Notice: the method is not intended to stringify the class. Only
     * reflections of the objects are allowed.
     *
     * @param array $reflection [visibility, name, implements, constants,
     *                          properties, methods]
     * @return string
     */
    public function stringifyRefobject(array $reflection)
    {
        $declaration = $this->stringifyDeclaration($reflection);
        $constants   = $this->stringifyConstants($reflection['constants']);
        $properties  = $this->stringifyProperties($reflection['properties']);
        $methods     = $this->stringifyMethods($reflection['methods']);

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
     * Notice: the method is not intended to stringify the class. Only
     * reflections of the objects are allowed.
     *
     * @param array $reflection [visibility, name, implements, constants,
     *                          properties, methods]
     * @return string
     */
    public function stringifyRefstruct(array $reflection)
    {
        // Remove all methods
        $reflection['methods'] = array();

        return $this->stringifyRefobject($reflection);
    }

    /**
     * @param \DateTime $date
     * @return string Date in format "j F, Y (H:i:s)": "31 December, 2017 (18:32:59)".
     */
    public function stringifyDate(\DateTime $date)
    {
        return $date->format('{j F, Y (H:i:s)}');
    }

    /**
     * @param mixed $_ Optional. Null by default.
     * @return string
     */
    public function stringifyNull($_ = null)
    {
        return 'null';
    }

    /**
     * @param mixed $resource
     * @return string
     */
    public function stringifyResource($resource)
    {
        return $this->stringifyUndefined($resource);
    }

    /**
     * @param mixed $var
     * @return string
     */
    public function stringifyUndefined($var)
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
    public function stringifyAsis($var)
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
    protected function translatePlural($context, $singular, $plural, $n)
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
