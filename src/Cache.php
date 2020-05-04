<?php

namespace Drutiny;

use Symfony\Component\Cache\CacheItem;

/**
 * @deprecated
 * A static cache handler.
 */
class Cache
{

    protected static $cache = [];

    public static function set($bin, $cid, $value)
    {
        Container::getLogger()->warning('Drutiny\Cache class is deprecated. Please use Drutiny\Container::cache() instead.');
        $pool = Container::cache($bin);
        $item = $pool->getItem($cid);
        $item->set($value)
        ->expiresAt(new \DateTime('+1 hour'));
        $pool->save($item);
        return true;
    }

    public static function get($bin, $cid)
    {
        Container::getLogger()->warning('Drutiny\Cache class is deprecated. Please use Drutiny\Container::cache() instead.');
        $pool = Container::cache($bin);
        return $pool->getItem($cid)->get();
    }

    public static function purge($bin = null)
    {
        Container::getLogger()->warning('Drutiny\Cache class is deprecated. Please use Drutiny\Container::cache() instead.');
        $pool = Container::cache($bin);
        $pool->clear();
        return true;
    }

    public static function delete($bin, $cid)
    {
        Container::getLogger()->warning('Drutiny\Cache class is deprecated. Please use Drutiny\Container::cache() instead.');
        $pool = Container::cache($bin);
        $pool->deleteItem($cid);
        return true;
    }
}
