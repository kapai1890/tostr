<?php

foreach (['Array Levels', 'Object Levels', 'Sample Types', 'Sample Classes'] as $title) {
    $name = str_replace(' ', '-', strtolower($title));
    $dummy = require __DIR__ . "/dummies/{$name}.php";

    echo PHP_EOL, $title, PHP_EOL;
    echo '========================================', PHP_EOL, PHP_EOL;

    if ($name == 'object-levels') {
        echo tostru($dummy);
    } else {
        echo tostr($dummy);
    }

    echo PHP_EOL;
}
