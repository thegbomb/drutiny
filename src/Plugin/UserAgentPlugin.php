<?php

namespace Drutiny\Plugin;

use Drutiny\Plugin;

class UserAgentPlugin extends Plugin {

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'http:user_agent';
    }

    public function configure()
    {
        $this->addField(
            'user_agent',
            "The User-Agent string for Drutiny to use on outbound HTTP requests."
            );
    }
}

 ?>
