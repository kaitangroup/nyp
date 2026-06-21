<?php

spl_autoload_register(function ($class) {

   
    $prefix = 'NYP\\';

    if (strpos($class, $prefix) !== 0) {
        return;
    }

    $relativeClass = str_replace($prefix, '', $class);

    $file = NYP_PLUGIN_PATH .
        'app/' .
        str_replace('\\', '/', $relativeClass) .
        '.php';

   

    if (file_exists($file)) {
        require_once $file;
    }
});