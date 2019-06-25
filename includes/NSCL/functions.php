<?php

if (!function_exists('is_numeric_natural_array')) {
    /**
     * @param array $array
     * @return bool
     */
    function is_numeric_natural_array(array $array)
    {
        foreach (array_keys($array) as $index => $key) {
            if ($index !== $key) {
                return false;
            }
        }

        return true;
    }
}

if (!function_exists('regex_combine')) {
    /**
     * Combine results of two subpatterns into single array.
     *
     * @param string $pattern
     * @param string $subject
     * @param int $keyIndex If there are no such index in matches then the result
     *                      will be a numeric array with appropriate values.
     * @param int $valueIndex If there are no such index in matches then the result
     *                        will be an array with appropriate keys but with empty
     *                        values (empty strings "").
     * @return array
     */
    function regex_combine($pattern, $subject, $keyIndex = -1, $valueIndex = 0)
    {
        $count  = (int)preg_match_all($pattern, $subject, $matches);
        $keys   = isset($matches[$keyIndex]) ? $matches[$keyIndex] : array();
        $values = isset($matches[$valueIndex]) ? $matches[$valueIndex] : array_fill(0, $count, '');

        if (!empty($values) && !empty($keys)) {
            return array_combine($keys, $values);
        } else {
            // Only $keys can be empty at this point (because we used array_fill()
            // for values)
            return $values;
        }
    }
}

if (!function_exists('regex_test')) {
    /**
     * @param string $pattern
     * @param string $subject
     * @param int $index The index of the result group.
     * @return bool
     */
    function regex_test($pattern, $subject, $index = 0)
    {
        $found = preg_match($pattern, $subject, $matches);
        return ($found && isset($matches[$index]));
    }
}

if (!function_exists('strradd')) {
    /**
     * Add number starting from the most right position of the string.
     *
     * @param string $str
     * @param int $add
     * @return string
     */
    function strradd($str, $add = 1)
    {
        for ($i = strlen($str) - 1; $i >= 0; $i--) {
            if ($str[$i] == '.' || $str[$i] == ',') {
                continue;
            }

            $sum  = (int)$str[$i] + $add;
            $tens = floor($sum / 10);

            $str[$i] = $sum - ($tens * 10);

            $add = $tens;

            if ($add == 0) {
                break;
            }
        }

        if ($add != 0) {
            $str = $add . $str;
        }

        return $str;
    }
}

if (!function_exists('typeof')) {
    /**
     * @param mixed $var
     * @return string The type of variable.
     */
    function typeof($var)
    {
        $type = strtolower(gettype($var));

        // Generalize or change the name of the type
        switch ($type) {
            case 'boolean': $type = 'bool';  break;
            case 'integer': $type = 'int';   break;
            case 'double':  $type = 'float'; break;

            case 'array':
                if (is_callable($var)) { // [%Object or class%, %Method name%]
                    $type = 'callback';
                }
                break;

            case 'object':
                if (is_callable($var)) {
                    $type = 'closure';
                } else if ($var instanceof \DateTime) {
                    $type = 'date';
                } else if ($var instanceof \NSCL\ToStr\AsIs) {
                    $type = 'asis';
                }
                break;

            case 'callable':
                if (is_string($var)) {
                    $type = 'function';
                } else {
                    $type = 'closure';
                }
                break;
        }

        return $type;
    }
}
