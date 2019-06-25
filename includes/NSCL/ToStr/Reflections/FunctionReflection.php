<?php

namespace NSCL\ToStr\Reflections;

class FunctionReflection extends \NSCL\ToStr\Reflection
{
    /**
     * @param mixed $input Function name.
     * @return array [name, arguments => [required, optional]]
     */
    protected function reflect($input)
    {
        if (function_exists($input)) {
            $reflection = new \ReflectionFunction($input);
            $arguments  = $this->reflectArguments($reflection->getClosure());
        } else {
            $arguments  = $this->noArguments();
        }

        return array('name' => $input, 'arguments' => $arguments);
    }

    /**
     * @return array [name, arguments => [required, optional]]
     */
    public function getReflection()
    {
        return parent::getReflection();
    }
}
