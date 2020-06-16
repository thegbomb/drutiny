<?php

namespace Drutiny\Plugin;

use Drutiny\Plugin;

class GithubPlugin extends Plugin {

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'github';
    }

    public function configure()
    {
        $this->addField(
            'personal_access_token',
            "github personal oauth token",
            static::FIELD_TYPE_CREDENTIAL
            );
    }
}

 ?>
