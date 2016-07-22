<?php


function autoload($classId)
{
    $classIdParts       = explode("\\", $classId);
    $classIdLength      = count($classIdParts);
    $className          = $classIdParts[$classIdLength - 1];
    $namespace          = $classIdParts[0];

    for ($i = 1; $i < $classIdLength - 1; $i++) {
        $namespace .= '/' . $classIdParts[$i];
    }

    $filename = __DIR__ . '/' . $namespace . '/' . $className . '.php';
    //echo "Autoloading '$filename''\n";
    if (file_exists($filename)) {
        include $filename;
    }
}

spl_autoload_register('autoload');
