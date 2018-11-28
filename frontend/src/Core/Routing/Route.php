<?php declare(strict_types=1);

namespace PN\Weblight\Core\Routing;

class Route
{
  public $method, $path, $handler;

  public function __construct(string $method, string $path, $handler)
  {
    $this->method = $method;
    $this->path = $path;
    $this->handler = $handler;
  }
}
