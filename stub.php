<?php

/**
 * @file
 * Stub file for the phar build.
 */

 use Drutiny\Kernel;
 use Drutiny\Console\Application;

 if (!in_array(PHP_SAPI, ['cli', 'phpdbg', 'embed'], true)) {
     echo 'Warning: The console should be invoked via the CLI version of PHP, not the '.PHP_SAPI.' SAPI'.PHP_EOL;
 }

set_time_limit(0);
ini_set('memory_limit', '-1');

require 'vendor/autoload.php';

const DRUTINY_LIB = __DIR__;

$version_filename = DRUTINY_LIB . '/VERSION';
$version = 'unknown';
if (file_exists($version_filename)) {
 $version = file_get_contents($version_filename);
}

$kernel = new Kernel('production');

$application = new Application($kernel, $version);
$application->run();
