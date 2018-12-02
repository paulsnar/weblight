<?php declare(strict_types=1);

namespace PN\Weblight\Core;

use PN\Weblight\Logging\LogRouter;

class AppContext
{
  /** @var DependencyContainer */
  protected $dc;

  public function __construct(DependencyContainer $dc)
  {
    $this->dc = $dc;
  }

  /** @return object */
  public function get(string $what)
  {
    return $this->dc->get($what);
  }
}
