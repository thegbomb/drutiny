<?php

namespace Drutiny\Http;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\Storage\Psr6CacheStorage;
use Kevinrob\GuzzleCache\Strategy\PrivateCacheStrategy;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Client
{
    use ContainerAwareTrait;

    protected $cache;

    public function __construct(ContainerInterface $container, FilesystemAdapter $cache)
    {
        $this->setContainer($container);
        $this->cache = $cache;
    }

    /**
     * Factory method to create a new guzzle client instance.
     */
    public function create(array $config = [])
    {
        if (!isset($config['handler'])) {
            $config['handler'] = HandlerStack::create();
        }

        foreach ($this->container->findTaggedServiceIds('http.middleware') as $id => $service) {
            $service = $this->container->get($id);
            $config['handler']->push(Middleware::mapRequest(function (RequestInterface $request) use ($service) {
                return $service->handle($request);
            }), $id);
        }

        $config['handler']->unshift(cache_middleware($this->cache), 'cache');
      // $config['handler']->after('cache', $this->container->get('logger'), 'logger');

        return new GuzzleClient($config);
    }
}

function cache_middleware($cache)
{
    static $middleware;
    if ($middleware) {
        return $middleware;
    }
    $storage = new Psr6CacheStorage($cache);
    $middleware = new CacheMiddleware(new PrivateCacheStrategy($storage));
    return $middleware;
}
