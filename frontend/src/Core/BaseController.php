<?php declare(strict_types=1);

namespace PN\Weblight\Core;

use PN\Weblight\HTTP\{Request, Response};

class BaseController implements ControllerInterface
{
  /** @var AppContext */
  protected $ctx;

  public function invoke(AppContext $ctx, string $method, Request $request): Response
  {
    $this->ctx = $ctx;
    try {
      return $this->$method($ctx, $request);
    } catch (MiddlewareInterrupt $e) {
      return $e->response;
    } finally {
      $this->ctx = null;
    }
  }

  protected function requireMiddleware(Request $request, string $name)
  {
    $instance = $this->ctx->get($name);
    $resp = $instance->invoke($this->ctx, $request);
    if ($resp !== null) {
      throw new MiddlewareInterrupt($resp);
    }
  }
}
