<?php

namespace NSCL\ToStr\Reflections;

class ObjectReflection extends \NSCL\ToStr\Reflection
{
    /**
     * @param mixed $input The object to reflect.
     * @return array [visibility, name, implements, constants, properties, methods]
     */
    protected function reflect($input)
    {
        $class = new \ReflectionClass($input);

        $constants  = $this->reflectConstants($input);
        $properties = $this->reflectProperties($input);
        $methods    = $this->reflectMethods($input);

        return array(
            'visibility' => array(
                'level'       => 'public',
                // It can't be abstract - it's already an instance of some class
                'is-abstract' => false,
                'is-final'    => $class->isFinal(),
                'is-static'   => false
            ),
            'name'       => $class->getName(),
            'implements' => $class->getInterfaceNames(),
            'constants'  => $constants,
            'properties' => $properties,
            'methods'    => $methods
        );
    }

    /**
     * Reflect class constants.
     *
     * @param mixed $object The object to reflect.
     * @return array [name, value]
     */
    protected function reflectConstants($object)
    {
        $class      = new \ReflectionClass($object);
        $constants  = $class->getConstants();
        $reflection = array();

        foreach ($constants as $name => $value) {
            $reflection[] = array('name' => $name, 'value' => $value);
        }

        return $reflection;
    }

    /**
     * Reflect class properies (fields).
     *
     * @param mixed $object The object to reflect.
     * @return array [visibility, name, value]
     */
    protected function reflectProperties($object)
    {
        $class      = new \ReflectionClass($object);
        $properties = $class->getProperties();
        $reflection = array();

        foreach ($properties as $property) { // Type of $property is \ReflectionProperty
            $visibility = $this->reflectVisibility($property);

            // Set the property accessible to get the value of private and
            // protected fields
            $property->setAccessible(true);
            $value = $property->getValue($object);

            $reflection[$property->getName()] = array(
                'visibility' => $visibility,
                'name'       => $property->getName(),
                'value'      => $value
            );
        }

        // Get also properties added as "$stdClass->newProperty = ...;"
        foreach ($object as $propertyName => $value) {
            if (array_key_exists($propertyName, $reflection)) {
                continue;
            }

            $reflection[$propertyName] = array(
                'visibility' => array(
                    'level'       => 'public',
                    'is-final'    => false,
                    'is-abstract' => false,
                    'is-static'   => false
                ),
                'name'       => $propertyName,
                'value'      => $value
            );
        }

        return $reflection;
    }

    /**
     * Reflect class methods.
     *
     * @param mixed $object The object to reflect.
     * @return array [visibility, name, arguments]
     */
    protected function reflectMethods($object)
    {
        $class      = new \ReflectionClass($object);
        $methods    = $class->getMethods();
        $reflection = array();

        foreach ($methods as $method) { // Type of $method is \ReflectionMethod
            $visibility = $this->reflectVisibility($method);
            $arguments  = $this->reflectArguments($method->getClosure($object));

            $reflection[] = array(
                'visibility' => $visibility,
                'name'       => $method->getName(),
                'arguments'  => $arguments
            );
        }

        return $reflection;
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
        $visibility = array(
            'level'       => 'public',
            'is-abstract' => false,
            'is-final'    => false,
            'is-static'   => false
        );

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
     * @return array [visibility, name, implements, constants, properties, methods]
     */
    public function getReflection()
    {
        return parent::getReflection();
    }
}
