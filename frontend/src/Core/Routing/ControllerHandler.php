<?php declare(strict_types=1);

namespace PN\Weblight\Core\Routing;

use PN\Weblight\Core\ContextfulRequest;
use PN\Weblight\HTTP\{Request, Response};

class ControllerHandler implements HandlerInterface
{
  /** @var string */
  public $controller, $method;

  public function __construct(string $controller, string $method)
  {
    $this->controller = $controller;
    $this->method = $method;
  }

  public function handle(Request $rq): Response
  {
    if ( ! ($rq instanceof ContextfulRequest)) {
      throw new \Exception('Controller invocation with non-contextful request');
    }

    $ctrl = $rq->ctx->get($this->controller);
    return $ctrl->invoke($this->method, $rq);
  }
}
