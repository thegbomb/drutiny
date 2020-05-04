<?php

namespace Drutiny\Audit\Filesystem;

use Drutiny\Audit;
use Drutiny\Sandbox\Sandbox;
use Drutiny\Annotation\Param;

/**
 * Large files
 * @Param(
 *  name = "max_size",
 *  description = "The maximum size in MegaBytes a directory should be.",
 *  type = "integer",
 *  default = 20
 * )
 * @Param(
 *  name = "path",
 *  description = "The path of the directory to check for size.",
 *  type = "string"
 * )
 */
class FsSize extends Audit
{

  /**
   * @inheritdoc
   */
    public function audit(Sandbox $sandbox)
    {
        $path = $sandbox->getParameter('path', '%files');
        $stat = $sandbox->drush(['format' => 'json'])->status();

        $path = strtr($path, $stat['%paths']);

        $size = trim($sandbox->exec("du -d 0 -m $path | awk '{print $1}'"));

        $max_size = (int) $sandbox->getParameter('max_size', 20);

      // Set the size in MB for rendering
        $sandbox->setParameter('size', $size);
      // Set the actual path.
        $sandbox->setParameter('path', $path);

        return $size < $max_size;
    }
}
