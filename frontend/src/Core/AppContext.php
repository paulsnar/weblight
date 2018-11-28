<?php declare(strict_types=1);

namespace PN\Weblight\Core;

use PN\Weblight\Logging\LogRouter;

class AppContext
{
  protected $dc, $logger;

  public function __construct(DependencyContainer $dc)
  {
    $this->dc = $dc;
    $this->logger = $dc->get(LogRouter::class);
  }

  public function get(string $what)
  {
    return $this->dc->get($what);
  }

  public function log(array $config)
  {
    $this->logger->dispatch($config);
  }
}
