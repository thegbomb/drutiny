<?php

namespace Drutiny\Plugin;

use Drutiny\Plugin;

class AuthorizationPlugin extends Plugin {

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'http:authorization';
    }

    public function configure()
    {
        $this->addField(
              'username',
              "The username to use for basic http digest authorization",
              Plugin::FIELD_TYPE_CREDENTIAL
            )
            ->addField(
              'password',
              "The password to use for basic http digest authorization",
              Plugin::FIELD_TYPE_CREDENTIAL
            );
    }
}

 ?>
