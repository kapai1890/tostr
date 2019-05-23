<?php

require __DIR__ . '/main.php';

// We'll need MessageBuilder in all functions in functions.php
global $tostr;

// Initialize $tostr
if (!isset($tostr) || !($tostr instanceof \tostr\MessageBuilder)) {
    $reflector   = new \tostr\Reflector();
    $stringifier = new \tostr\Stringifier($reflector);
    $tostr       = new \tostr\MessageBuilder($reflector, $stringifier);
}

if (!function_exists('tostr')) {
    /**
     * @param mixed[] $vars
     * @return string
     *
     * @global \tostr\MessageBuilder $tostr
     */
    function tostr(...$vars)
    {
        global $tostr;

        // Convert root strings into `messages` (output them as is, without "")
        $vars = array_map(function ($var) {
            if (is_string($var) && !is_numeric($var)) {
                $trimmed = trim($var);

                if (!empty($trimmed)) {
                    // Show without ""
                    return new \tostr\AsIs($trimmed);
                }
            }

            // Otherwise don't change the output method
            return $var;
        }, $vars);

        return $tostr->buildMessage($vars, 5);
    }
}

if (!function_exists('tostrs')) {
    /**
     * @param mixed[] $vars
     * @return string
     *
     * @global \tostr\MessageBuilder $tostr
     */
    function tostrs(...$vars)
    {
        global $tostr;
        return $tostr->buildMessage($vars, 5);
    }
}

if (!function_exists('tostrms')) {
    /**
     * @param string $message The message to print not strictly.
     * @param mixed[] $vars
     * @return string
     *
     * @global \tostr\MessageBuilder $tostr
     */
    function tostrms($message, ...$vars)
    {
        global $tostr;

        $vars = array_merge([\tostr\asis($message)], $vars);
        return $tostr->buildMessage($vars, 5);
    }
}

if (!function_exists('tostrx')) {
    /**
     * @param mixed $var
     * @param string $type
     * @param int $maxDepth Optional. 5 by default.
     * @return string
     *
     * @global \tostr\MessageBuilder $tostr
     */
    function tostrx($var, $type, $maxDepth = 5)
    {
        global $tostr;
        return $tostr->buildAs($var, $type, $maxDepth) . PHP_EOL;
    }
}

if (!function_exists('asis')) {
    /**
     * @param mixed $var
     * @return \tostr\AsIs
     */
    function asis($var)
    {
        return \tostr\asis($var);
    }
}
