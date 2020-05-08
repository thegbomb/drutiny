<?php

namespace Drutiny\Report;

use Drutiny\Assessment;
use Drutiny\Profile;

interface FormatInterface
{
    public function getName():string;
    public function setOptions(array $options = []):FormatInterface;
    public function render(Profile $profile, Assessment $assessment);
    public function getExtension():string;
}
