<?php

namespace NSCL\ToStr\Reflections;

class CallbackReflection extends \NSCL\ToStr\Reflection
{
    /**
     * @param mixed $input An array like [%class or object%, %method name%].
     * @return array [name, arguments => [required, optional]], where
     *               <b>name</b> is <i>"Class::method"</i>.
     */
    protected function reflect($input)
    {
        if (count($input) < 2) {
            // Unknown format
            return array('name' => '?::?', 'arguments' => $this->noArguments());
        }

        $holder = $input[0];
        $class  = is_object($holder) ? get_class($holder) : $holder;
        $method = $input[1];

        if (class_exists($class) && method_exists($class, $method)) {
            $reflection = new \ReflectionMethod($class, $method);
            $arguments  = $this->reflectArguments($reflection->getClosure($holder));
        } else {
            $arguments  = $this->noArguments();
        }

        return array('name' => "{$class}::{$method}", 'arguments' => $arguments);
    }

    /**
     * @return array [name, arguments => [required, optional]], where
     *               <b>name</b> is <i>"Class::method"</i>.
     */
    public function getReflection()
    {
        return parent::getReflection();
    }
}
