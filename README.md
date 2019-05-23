# ToStr
Convert any value to string with this tool.

# Requirements
* PHP 5.6+

# Installation
* Include `main.php` file to use ToStr as a module in your project.
* Or include `standalone.php` to use ToStr as ready-to-use solution, with functions `tostr`, `tostrs` and `tostrx`.

# Functions
When loaded using `standalone.php`, the module will add 3 functions:
1. **tostr(...$vars)** - converts all passed values into single string message (with newline character in the end). All root strings will be printed without quotes "".
```php
$ tostr('Offset:', 3);
> Offset: 3

$ tostr(['offset' => 3]);
> ["offset" => 3]
```

2. **tostrs(...$vars)** - same as previous, but always prints all strings with quotes "".
```php
$ tostrs('Offset:', 3);
> "Offset:" 3
```

3. **tostrms($message, ...$vars)** - same as tostrs(), but prints the $message without quotes "".
```php
$ tostrms('Offset:', 'three');
> Offset: "three"
```

4. **tostrx($var, $type[, $maxDepth = 5])** - converts the passed value indicating it's type manually.
```php
$ tostr('count');
> count

$ tostrs('count');
> "count"

$ tostrx('count', 'function');
> function count($array_or_countable\[, $mode\]) { ... }
```

# Examples
* **Boolean**: `true`, `false`.
* **Boolean** _(yes/no format)_: `yes`, `no`.
* **Integer**: `57`, `-273`.
* **Float**: `3.14159`, `0.018`, `100.01`.
* **String**: `"Hello"`, `"89"`.
* **Indexed array**: `[1, 2, 3]`.
* **Associative array**: `[1 => "Aa", 2 => "Bb", 26 => "Zz"]`.
* **Iterable object**: `{1, 2, 3}`.
* **Date** _(DateTime object)_: `{18 April, 2019 (08:10:13)}`.
* **Null**: `null`.
* **As is**: `I'm a string without quotes :P`.
* **Closure**: `function ($r, $g, $b[, $a]) { ... }`.
* **Function**: `function tostrx($var, $type[, $maxDepth]) { ... }`.
* **Callback**: `Exception::getMessage() { ... }`.
* **Object**:
```php
class Exception implements Throwable
{
    protected $message = "Test exception";
    private $string = "";
    protected $code = 0;
    protected $file = ".../tostr/tests/dummies/sample-types.php";
    protected $line = 15;
    private $trace = [["file" => ".../Test.php", "line" => 14, "function" => "require"]];
    private $previous;

    final private function __clone() { ... }
    public function __construct() { ... }
    public function __wakeup() { ... }
    final public function getMessage() { ... }
    final public function getCode() { ... }
    final public function getFile() { ... }
    final public function getLine() { ... }
    final public function getTrace() { ... }
    final public function getPrevious() { ... }
    final public function getTraceAsString() { ... }
    public function __toString() { ... }
}
```
* **Structure** _(skips methods)_:
```php
class Exception implements Throwable
{
    protected $message = "Test exception";
    private $string = "";
    protected $code = 0;
    protected $file = ".../tostr/tests/dummies/sample-types.php";
    protected $line = 15;
    private $trace = [["file" => ".../Test.php", "line" => 14, "function" => "require"]];
    private $previous;
}
```

# License
The project is licensed under the [MIT License](https://opensource.org/licenses/MIT).
