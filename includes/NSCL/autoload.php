<?php

namespace NSCL;

if (!defined(__NAMESPACE__ . '\ROOT')) {
    define(__NAMESPACE__ . '\ROOT', __DIR__ . DIRECTORY_SEPARATOR);
}

spl_autoload_register(function ($className) {
    // "Namespace\Subpackage\ClassX"
    $className = ltrim($className, '\\');

    if (strpos($className, __NAMESPACE__) !== 0) {
        return false;
    }

    // "Subpackage\ClassX"
    $pluginFile = str_replace(__NAMESPACE__ . '\\', '', $className);
    // "Subpackage/ClassX"
    $pluginFile = str_replace('\\', DIRECTORY_SEPARATOR, $pluginFile);
    // "Subpackage/ClassX.php"
    $pluginFile .= '.php';

    require ROOT . $pluginFile;

    return true;
});
