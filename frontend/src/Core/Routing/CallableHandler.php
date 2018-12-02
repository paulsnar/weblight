<?php declare(strict_types=1);

namespace PN\Weblight\Core\Routing;

use PN\Weblight\HTTP\{Request, Response};

class CallableHandler implements HandlerInterface
{
  /** @var callable */
  protected $handler;

  public function __construct(callable $handler)
  {
    $this->handler = $handler;
  }

  public function handle(Request $rq): Response
  {
    return ($this->handler)($rq);
  }
}
