<?php

/*
 * Project Name: ToStr
 * Project URI: https://github.com/byevhen2/tostr
 * Description: Convert any value to string with this tool.
 * Version: 3.0
 * Author: Biliavskyi Yevhen
 * Author URI: https://github.com/byevhen2
 * License: MIT
 */

if (!defined('ToStr')) {
    define('ToStr', '3.0');

    // Load NSCL classes and functions
    require __DIR__ . '/includes/NSCL/autoload.php';
    require __DIR__ . '/includes/NSCL/functions.php';

    // Load own functions
    require __DIR__ . '/includes/functions.php';
}
