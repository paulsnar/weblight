<?php declare(strict_types=1);

namespace PN\Weblight\Core\Routing;

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
    if ( ! ($rq instanceof HandlerInterface)) {
      throw new \Exception('Controller invocation with non-contextful request');
    }

    $ctrl = $rq->ctx->get($this->controller);
    return $ctrl->invoke($rq->ctx, $this->method, $rq);
  }
}
