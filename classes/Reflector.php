<?php

namespace tostr;

class Reflector
{
    // Arrays-constants available only since PHP 7
    protected $NO_ARGUMENTS = ['required' => [], 'optional' => []];

    /**
     * @param \Closure $closure
     * @return array [required => [...], optional => [...]]
     */
    protected function reflectArguments(\Closure $closure)
    {
        // $args = "Closure Object( [parameter] => Array ( [$x] => <required> [$y] => <optional> ... ) )"
        $args = print_r($closure, true);
        // $args = ["x" => "required", "y" => "optional", ...]
        $args = regex_combine('/\[\$(\w+)\] => <(\w+)>/', $args, 1, 2);

        $arguments = $this->NO_ARGUMENTS;

        foreach ($args as $name => $status) {
            $arguments[$status][] = $name;
        }

        return $arguments;
    }

    /**
     * @param \Closure $closure
     * @return array [name, arguments => [required, optional]]
     */
    public function reflectClosure(\Closure $closure)
    {
        $arguments = $this->reflectArguments($closure);

        return ['name' => '', 'arguments' => $arguments];
    }

    /**
     * @param string $functionName
     * @return array [name, arguments => [required, optional]]
     */
    public function reflectFunction($functionName)
    {
        if (function_exists($functionName)) {
            $reflection = new \ReflectionFunction($functionName);
            $arguments  = $this->reflectArguments($reflection->getClosure());
        } else {
            $arguments  = $this->NO_ARGUMENTS;
        }

        return ['name' => $functionName, 'arguments' => $arguments];
    }

    /**
     * @param array $callback An array like [%class or object%, %method name%].
     * @return array [name, arguments => [required, optional]], where
     *               <b>name</b> is <i>"Class::method"</i>.
     */
    public function reflectCallback(array $callback)
    {
        if (count($callback) < 2) {
            // This will become "?::?() { ... }" in Stringifier::stringifyCallback()
            return ['name' => '?::?', 'arguments' => $this->NO_ARGUMENTS];
        }

        $holder = $callback[0];
        $class  = is_object($holder) ? get_class($holder) : $holder;
        $method = $callback[1];

        if (class_exists($class) && method_exists($class, $method)) {
            $reflection = new \ReflectionMethod($class, $method);
            $arguments  = $this->reflectArguments($reflection->getClosure($holder));
        } else {
            $arguments  = $this->NO_ARGUMENTS;
        }

        return ['name' => "{$class}::{$method}", 'arguments' => $arguments];
    }

    /**
     * Reflect visibility settings of the class property/method.
     *
     * @param \Reflector $reflection
     * @return array [level, is-abstract, is-final, is-static] where
     *               <b>level</b> is <i>public</i>|<i>protected</i>|<i>private</i>.
     */
    protected function reflectVisibility(\Reflector $reflection)
    {
        $visibility = [
            'level'       => 'public',
            'is-abstract' => false,
            'is-final'    => false,
            'is-static'   => false
        ];

        // private|protected
        if ($reflection->isPrivate()) {
            $visibility['level'] = 'private';
        } else if ($reflection->isProtected()) {
            $visibility['level'] = 'protected';
        }

        // abstract|final
        if (method_exists($reflection, 'isAbstract') && $reflection->isAbstract()) {
            $visibility['is-abstract'] = true;
        } else if (method_exists($reflection, 'isFinal') && $reflection->isFinal()) {
            $visibility['is-final'] = true;
        }

        // static
        if ($reflection->isStatic()) {
            $visibility['is-static'] = true;
        }

        return $visibility;
    }

    /**
     * Reflect class constants.
     *
     * @param mixed $object The object to reflect.
     * @return array [name, value]
     */
    public function reflectConstants($object)
    {
        $class      = new \ReflectionClass($object);
        $constants  = $class->getConstants();
        $reflection = [];

        foreach ($constants as $name => $value) {
            $reflection[] = ['name' => $name, 'value' => $value];
        }

        return $reflection;
    }

    /**
     * Reflect class properies (fields).
     *
     * @param mixed $object The object to reflect.
     * @return array [visibility, name, value]
     */
    public function reflectProperties($object)
    {
        $class      = new \ReflectionClass($object);
        $properties = $class->getProperties();
        $reflection = [];

        foreach ($properties as $property) { // Type of $property is \ReflectionProperty
            $visibility = $this->reflectVisibility($property);

            // Set the property accessible to get the value of private and
            // protected fields
            $property->setAccessible(true);
            $value = $property->getValue($object);

            $reflection[$property->getName()] = [
                'visibility' => $visibility,
                'name'       => $property->getName(),
                'value'      => $value
            ];
        }

        // Get also properties added as "$stdClass->newProperty = ...;"
        foreach ($object as $propertyName => $value) {
            if (array_key_exists($propertyName, $reflection)) {
                continue;
            }

            $reflection[$propertyName] = [
                'visibility' => [
                    'level'       => 'public',
                    'is-final'    => false,
                    'is-abstract' => false,
                    'is-static'   => false
                ],
                'name'       => $propertyName,
                'value'      => $value
            ];
        }

        return $reflection;
    }

    /**
     * Reflect class methods.
     *
     * @param mixed $object The object to reflect.
     * @return array [visibility, name, arguments]
     */
    public function reflectMethods($object)
    {
        $class      = new \ReflectionClass($object);
        $methods    = $class->getMethods();
        $reflection = [];

        foreach ($methods as $method) { // Type of $method is \ReflectionMethod
            $visibility = $this->reflectVisibility($method);
            $arguments  = $this->reflectArguments($method->getClosure($object));

            $reflection[] = [
                'visibility' => $visibility,
                'name'       => $method->getName(),
                'arguments'  => $arguments
            ];
        }

        return $reflection;
    }

    /**
     * @param mixed $object The object to reflect.
     * @return array [visibility, name, implements, constants, properties, methods]
     */
    public function reflectObject($object)
    {
        $class = new \ReflectionClass($object);

        $constants  = $this->reflectConstants($object);
        $properties = $this->reflectProperties($object);
        $methods    = $this->reflectMethods($object);

        return [
            'visibility' => [
                'level'       => 'public',
                // It can't be abstract - it's already an instance of some class
                'is-abstract' => false,
                'is-final'    => $class->isFinal(),
                'is-static'   => false
            ],
            'name'       => $class->getName(),
            'implements' => $class->getInterfaceNames(),
            'constants'  => $constants,
            'properties' => $properties,
            'methods'    => $methods
        ];
    }
}
