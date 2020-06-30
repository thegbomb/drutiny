<?php

namespace Drutiny\Http\Audit;

use Drutiny\Audit;

abstract class Http extends Audit
{
    use HttpTrait {
        configure as HttpTrait_configure;
    }
}
