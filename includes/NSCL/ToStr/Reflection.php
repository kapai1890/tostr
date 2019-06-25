<?php

namespace NSCL\ToStr;

abstract class Reflection
{
    protected $reflection = array();

    /**
     * @param mixed $input Function name, callback, closure, object etc.
     */
    public function __construct($input)
    {
        $this->reflection = $this->reflect($input);
    }

    /**
     * @param mixed $input Function name, callback, closure, object etc.
     * @return array
     */
    abstract protected function reflect($input);

    /**
     * @return array Reflection of arguments, properties, methods etc.
     */
    public function getReflection()
    {
        return $this->reflection;
    }

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

        $arguments = $this->noArguments();

        foreach ($args as $name => $status) {
            $arguments[$status][] = $name;
        }

        return $arguments;
    }

    /**
     * @return array [required => [], optional => []]
     */
    protected function noArguments()
    {
        return array('required' => array(), 'optional' => array());
    }
}
