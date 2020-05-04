<?php

namespace Drutiny\Docs;

use Drutiny\Registry;
use Symfony\Component\Yaml\Yaml;

class PolicyDocsGenerator extends DocsGenerator
{

    public function buildPolicyDocumentation(\Drutiny\Policy $policy)
    {
        $filepath = $policy->get('filepath');
        $root = $this->findRoot($filepath);
        $relative_path = str_replace($root, '', $filepath);
        $package = $this->findPackage($filepath);

        $md = [];
        $md[] = $link = strtr('## title', [
        'title' => $policy->get('title'),
        'name' => $policy->get('name'),
        ]);

        $md[] = "**Name**: `" . $policy->get('name') . "` [[View Source](https://github.com/" . $package . "/tree/master" . $relative_path . ")]  ";
        $md[] = "**Package**: `$package`  ";
        $md[] = "**Class**: `" . $policy->get('class') . "`";
        $md[] = '';
        $md[] = $policy->get('description');

        $audit = (new \Drutiny\Registry)->getAuditMedtadata($policy->get('class'));

        $params = $policy->get('parameters');
        foreach ($audit->params as $param) {
            if (isset($params[$param->name])) {
                $params[$param->name] = array_merge($param->toArray(), $params[$param->name]);
            }
        }

        if (!empty($params)) {
            $md[] = '';
            $md[] = '### Parameters';
            $md[] = 'Name | Type | Description | Default';
            $md[] = '-- | -- | -- | --';

            foreach ($params as $name => $param) {
              // $params may not correctly conform so this it just to prevent the
              // php notices.
                $param = array_merge([
                'type' => '',
                'description' => '',
                'default' => '',
                ], $param);
                $md[] = strtr('Name | Type | Description | Default', [
                'Name' => $name,
                'Type' => $param['type'],
                'Description' => $param['description'],
                'Default' => str_replace(PHP_EOL, '<br>', Yaml::dump($param['default'])),
                ]);
            }
        }

        $tokens = $policy->get('tokens');
        if (!empty($tokens)) {
            $md[] = '';
            $md[] = '### Tokens';
            $md[] = 'Name | Type | Description | Default';
            $md[] = '-- | -- | -- | --';

            foreach ($tokens as $name => $token) {
                $md[] = strtr('Name | Type | Description | Default', [
                'Name' => $name,
                'Type' => $token['type'],
                'Description' => $token['description'],
                'Default' => $token['default'],
                ]);
            }
        }

        return implode(PHP_EOL, $md);
    }
}
