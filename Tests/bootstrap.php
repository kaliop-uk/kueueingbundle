<?php

if (!file_exists($file = __DIR__.'/../vendor/autoload.php')) {
    throw new \RuntimeException('Install the dependencies to run the test suite.');
}

$loader = require $file;

$classLoader = new \Composer\Autoload\ClassLoader();
$classLoader->addClassMap(array("AppKernel" => __DIR__.'/app/AppKernel.php'));
$classLoader->register();
