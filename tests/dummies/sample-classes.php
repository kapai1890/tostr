<?php

namespace tostr;

class OnlyConstants
{
    const NULL_CONSTANT = null;
    const NUMERIC_STRING = '89';
}

class OnlyProperties
{
    public $unsetProperty;
    protected $nullValue = null;
    private $number = 57;
}

class OnlyMethods
{
    public function __construct() {}
    public function getId() {}
    public function findById($id) {}
    protected function count($countHidden = false) {}
    protected function translate($text, $domain = '') {}
    public static function getInstance() {}
}

class NoConstants
{
    public $unsetProperty;
    protected $nullValue = null;
    private $number = 57;

    public function __construct() {}
    public function getId() {}
    public function findById($id) {}
    protected function count($countHidden = false) {}
    protected function translate($text, $domain = '') {}
    public static function getInstance() {}
}

class NoProperties
{
    const NULL_CONSTANT = null;
    const NUMERIC_STRING = '89';

    public function __construct() {}
    public function getId() {}
    public function findById($id) {}
    protected function count($countHidden = false) {}
    protected function translate($text, $domain = '') {}
    public static function getInstance() {}
}

class NoMethods
{
    const NULL_CONSTANT = null;
    const NUMERIC_STRING = '89';

    public $unsetProperty;
    protected $nullValue = null;
    private $number = 57;
}

class AllInOne
{
    const NULL_CONSTANT = null;
    const NUMERIC_STRING = '89';

    public $unsetProperty;
    protected $nullValue = null;
    private $number = 57;

    public function __construct() {}
    public function getId() {}
    public function findById($id) {}
    protected function count($countHidden = false) {}
    protected function translate($text, $domain = '') {}
    public static function getInstance() {}
}

return [
    'Only constants:'  => new OnlyConstants(),
    'Only properties:' => new OnlyProperties(),
    'Only methods:'    => new OnlyMethods(),
    'No constants:'    => new NoConstants(),
    'No properties:'   => new NoProperties(),
    'No methods:'      => new NoMethods(),
    'All in one:'      => new AllInOne()
];
