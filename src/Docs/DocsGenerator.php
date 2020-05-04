<?php

namespace Drutiny\Docs;

use Drutiny\Registry;
use Symfony\Component\Yaml\Yaml;

abstract class DocsGenerator
{

    protected function findRoot($filepath)
    {
        $checked_paths = [];
        while ($filepath) {
            $filepath = dirname($filepath);
            $composer_filepath = $filepath . '/composer.json';

            if (in_array($composer_filepath, $checked_paths)) {
                break;
            }

            $checked_paths[] = $composer_filepath;
            if (file_exists($composer_filepath)) {
                break;
            }
        }
        return dirname($composer_filepath);
    }

    protected function findPackage($filepath)
    {
        if (!$root = $this->findRoot($filepath)) {
            return 'drutiny/content';
        }
        $composer_filepath = $root . '/composer.json';

        if (!$json = @file_get_contents($composer_filepath)) {
            return 'drutiny/content';
        }
        $composer = json_decode($json, true);
        return $composer['name'];
    }
}
