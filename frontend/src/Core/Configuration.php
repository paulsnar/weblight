<?php declare(strict_types=1);

namespace PN\Weblight\Core;

use const PN\Weblight\ROOT_PRIVATE;
use function PN\Weblight\path_join;

class Configuration
{
  public $values = [ ];

  public function __construct()
  {
    $this->values = require path_join(ROOT_PRIVATE, 'config.php');
  }
}
