<?php declare(strict_types=1);

namespace PN\Weblight\Core;

use PN\Weblight\HTTP\{Request, Response};

interface ControllerInterface
{
  public function invoke(string $method, Request $rq): Response;
}
