<?php

/**
 * Ignore the real type of the value in future.
 *
 * @param mixed $value
 * @return \NSCL\ToStr\AsIs
 */
function asis($value)
{
    return new \NSCL\ToStr\AsIs($value);
}

/**
 * @return \NSCL\ToStr\StringBuilder
 *
 * @global \NSCL\ToStr\StringBuilder $tostr
 */
function get_default_string_builder()
{
    global $tostr;

    if (!isset($tostr)) {
        $stringifier = new \NSCL\ToStr\Stringifier();
        $tostr = new \NSCL\ToStr\StringBuilder($stringifier);
    }

    return $tostr;
}

/**
 * Build message, but don't wrap root strings with "".
 *
 * @param mixed[] $vars
 * @return string
 */
function tostr(...$vars)
{
    // Convert root strings into `messages` (output them as is, without "")
    $vars = array_map(function ($var) {
        if (is_string($var) && !is_numeric($var)) {
            $trimmed = trim($var);

            if (!empty($trimmed)) {
                // Show without ""
                return asis($trimmed);
            }
        }

        // Otherwise don't change the output method
        return $var;
    }, $vars);

    return get_default_string_builder()->buildString($vars) . PHP_EOL;
}

/**
 * Build message in strict mode (wrap all string with ""), except for the first
 * message.
 *
 * @param string $message The message to print not strictly.
 * @param mixed[] $vars
 * @return string
 */
function tostrms($message, ...$vars)
{
    $vars = array_merge(array(asis($message)), $vars);
    return get_default_string_builder()->buildString($vars) . PHP_EOL;
}

/**
 * Build message in strict mode (wrap all string with "").
 *
 * @param mixed[] $vars
 * @return string
 */
function tostrs(...$vars)
{
    return get_default_string_builder()->buildString($vars) . PHP_EOL;
}

/**
 * Build the message also going into the nested objects.
 *
 * @param mixed $var Any object.
 * @param int $maxDepth Optional. -1 by default (auto detect).
 * @param array $recursiveClasses Optional. [stdClass] by default.
 * @return string
 */
function tostru($var, $maxDepth = -1, $recursiveClasses = array())
{
    return get_default_string_builder()->buildStringWithObjects($var, $maxDepth, $recursiveClasses) . PHP_EOL;
}

/**
 * Convert value to string, indicating it's type manually.
 *
 * @param mixed $var
 * @param string $type
 * @param int $maxDepth Optional. -1 by default (auto detect).
 * @return string
 */
function tostrx($vars, $type, $maxDepth = -1)
{
    return get_default_string_builder()->buildStringAs($vars, $type, $maxDepth) . PHP_EOL;
}

/**
 * Convert boolean into "yes"/"no" string.
 *
 * @param bool $value
 * @return string
 */
function yesno($value)
{
    return tostrx($value, 'yesno');
}
