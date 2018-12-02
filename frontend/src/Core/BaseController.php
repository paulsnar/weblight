<?php declare(strict_types=1);

namespace PN\Weblight\Core;

use PN\Weblight\HTTP\{Request, Response};

class BaseController implements ControllerInterface
{
  public function invoke(string $method, Request $request): Response
  {
    try {
      return $this->$method($request);
    } catch (MiddlewareInterrupt $e) {
      return $e->response;
    }
  }

  protected function requireMiddleware(Request $request, string $name)
  {
    $instance = $request->ctx->get($name);
    $resp = $instance->invoke($request);
    if ($resp !== null) {
      throw new MiddlewareInterrupt($resp);
    }
  }
}
