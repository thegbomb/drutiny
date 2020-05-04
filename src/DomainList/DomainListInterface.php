<?php

namespace Drutiny\DomainList;

interface DomainListInterface
{

  /**
   * @return array list of domains.
   */
    public function getDomains(array $options = []);
    public function getOptionsDefinitions();
}
