<?php

namespace Drutiny\Http\Middleware;

use Drutiny\Http\MiddlewareInterface;
use Drutiny\Plugin\UserAgentPlugin;
use Drutiny\Plugin\PluginRequiredException;
use Psr\Http\Message\RequestInterface;

class UserAgent implements MiddlewareInterface
{

    protected $config;

  /**
   * @param $config @config service.
   */
    public function __construct(UserAgentPlugin $plugin)
    {
        try {
          $this->config = $plugin->load();
        }
        catch (PluginRequiredException $e){}
    }

  /**
   * {@inheritdoc}
   */
    public function handle(RequestInterface $request)
    {
        return isset($this->config->user_agent) ? $request->withHeader('User-Agent', $this->config['user_agent']) : $request;
    }
}
