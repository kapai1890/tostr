<?php

namespace NSCL\ToStr\Reflections;

class ClosureReflection extends \NSCL\ToStr\Reflection
{
    /**
     * @param mixed $input A closure object (instance of \Closure).
     * @return array [name, arguments => [required, optional]]
     */
    protected function reflect($input)
    {
        $arguments = $this->reflectArguments($input);

        return array('name' => '', 'arguments' => $arguments);
    }

    /**
     * @return array [name, arguments => [required, optional]]
     */
    public function getReflection()
    {
        return parent::getReflection();
    }
}
