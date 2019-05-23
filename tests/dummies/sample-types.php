<?php

return [
    'bool'          => [true, false],
    'int'           => [57, -273],
    'float'         => [3.14159, 0.018, 100.01],
    'string'        => ['Hello', '89'],
    'numeric array' => [1, 2, 3],
    'assoc. array'  => [1 => 'Aa', 2 => 'Bb', 26 => 'Zz'],
    'date'          => new DateTime(),
    'null'          => null,
    'as is'         => new \tostr\AsIs("I'm a string without quotes"),
    'closure'       => function ($r, $g, $b, $a = 1.0) {},
    'callback'      => [new Exception(), 'getMessage'],
    'object'        => new Exception('Test exception')
];
