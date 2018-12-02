<?php declare(strict_types=1);

namespace PN\Weblight\Core;

use PN\Weblight\HTTP\{Request, Response};

interface MiddlewareInterface
{
  public function invoke(Request $rq, ...$arguments): ?Response;
}
