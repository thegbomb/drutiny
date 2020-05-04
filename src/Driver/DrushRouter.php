<?php

namespace Drutiny\Driver;

use Drutiny\Sandbox\Sandbox;
use Drutiny\Target\TargetInterface;
use Drutiny\Container;
use Composer\Semver\Comparator;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Find the correct drush driver to use.
 */
class DrushRouter
{

  /**
   * Derive a drush driver from a given Sandbox.
   *
   * @param Sandbox $sandbox
   * @param array $options
   * @return Drutiny\Driver\DrushDriver
   */
    public static function create(Sandbox $sandbox, $options = [])
    {
        return self::createFromTarget($sandbox->getTarget(), $options);
    }

  /**
   * Derive a drush driver from a given Target.
   */
    public static function createFromTarget(TargetInterface $target, $options = [])
    {
        try {
            $info = $target->getOptions();
            $launchers = ['drush-launcher', 'drush.launcher', 'drush'];
            $binary = 'which ' . implode(' || which ', $launchers);

            $info = trim($target->exec('$(' . $binary . ') -r ' . $info['root'] . ' status --format=json'));
            $info = \Drutiny\Utility::jsonDecodeDirty($info, true);

          // Hack for Acquia Cloud to force use drush9 if drush version reported is v9.
            if (Comparator::greaterThanOrEqualTo($info['drush-version'], '9.0.0')) {
                array_unshift($launchers, 'drush9');
                $binary = 'which ' . implode(' || which ', $launchers);
            }

            $binary = trim($target->exec($binary));
            $info = trim($target->exec($binary . ' -r ' . $info['root'] . ' status --format=json'));
            $info = json_decode($info, true);
        } catch (ProcessFailedException $e) {
            Container::getLogger()->error($e->getProcess()->getOutput());
            throw $e;
        }

        switch (true) {
            case Comparator::greaterThanOrEqualTo($info['drush-version'], '9.0.0'):
                $driver = Drush9Driver::createFromTarget($target, $binary);
                break;

            default:
                $driver = DrushDriver::createFromTarget($target, $binary);
        }
        $driver->setOptions($options);
        return $driver;
    }
}
