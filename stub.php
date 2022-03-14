<?php

use Drutiny\Kernel;
use Drutiny\Console\Application;

if (!in_array(PHP_SAPI, ['cli', 'phpdbg', 'embed'], true)) {
    echo 'Warning: The console should be invoked via the CLI version of PHP, not the '.PHP_SAPI.' SAPI'.PHP_EOL;
}

list($major, $minor, ) = explode('.', phpversion());

// Identify PHP versions lower than PHP 7.4.
if ($major < 7 || ($major == 7 && $minor < 4)) {
  echo "ERROR: Application requires PHP 7.4 or later. Currently running: ".phpversion()."\n";
  exit;
}

set_time_limit(0);

$lib = Phar::running() ?: '.';
define('DRUTINY_LIB', $lib);

require DRUTINY_LIB.'/vendor/autoload.php';

$version_files = [DRUTINY_LIB.'/VERSION', dirname(__DIR__).'/VERSION'];

// Load in the version if it can be found.
$versions = array_filter(array_map(function($file) {
  return file_exists($file) ? file_get_contents($file) : FALSE;
}, $version_files));

// Load from git.
if (empty($versions) && !Phar::running() && file_exists(DRUTINY_LIB . '/.git') && $git_bin = exec('which git')) {
  $versions[] = exec(sprintf('%s -C %s branch --no-color | cut -b 3-', $git_bin, DRUTINY_LIB)) . '-dev';
}

// Fallback option.
$versions[] = 'dev';

$kernel = new Kernel('production');

$application = new Application($kernel, reset($versions));
$application->run();
