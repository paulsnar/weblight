<?php declare(strict_types=1);

namespace PN\Weblight\Core\Routing;

class ControllerHandler
{
  public $controller, $method;

  public function __construct(string $controller, string $method)
  {
    $this->controller = $controller;
    $this->method = $method;
  }
}
