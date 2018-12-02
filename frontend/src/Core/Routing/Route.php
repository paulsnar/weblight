<?php declare(strict_types=1);

namespace PN\Weblight\Core\Routing;

class Route
{
  /** @var string|string[] */
  public $method;

  /** @var string */
  public $path;

  /** @var ControllerHandler|StaticServeHandler|callable */
  public $handler;

  public function __construct($method, string $path, $handler)
  {
    $this->method = $method;
    $this->path = $path;
    $this->handler = $handler;
  }
}
