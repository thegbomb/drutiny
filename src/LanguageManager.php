<?php

namespace Drutiny;

use Symfony\Component\DependencyInjection\ContainerInterface;

class LanguageManager {
    protected $container;
    protected $lang_code;
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getCurrentLanguage()
    {
        return $this->lang_code ?? $this->container->getParameter('language_default');
    }

    public function getDefaultLanguage()
    {
        return $this->container->getParameter('language_default');
    }

    public function setLanguage($lang_code = 'en')
    {
        $this->lang_code = $lang_code;
    }
}
