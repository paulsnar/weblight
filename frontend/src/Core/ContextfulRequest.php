<?php declare(strict_types=1);

namespace PN\Weblight\Core;

use PN\Weblight\HTTP\Request;

class ContextfulRequest extends Request
{
  /** @var AppContext */
  public $ctx;
}
