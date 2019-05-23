<?php

/*
 * Project Name: ToStr
 * Project URI: https://github.com/biliavskyi.yevhen/tostr
 * Description: Convert any value to string with this tool.
 * Version: 2.0
 * Author: Biliavskyi Yevhen
 * Author URI: https://github.com/biliavskyi.yevhen
 * License: MIT
 */

if (!class_exists('tostr\MessageBuilder')) {
    require __DIR__ . '/includes/functions.php';

    require __DIR__ . '/classes/AsIs.php';
    require __DIR__ . '/classes/Reflector.php';
    require __DIR__ . '/classes/Stringifier.php';
    require __DIR__ . '/classes/MessageBuilder.php';
}
