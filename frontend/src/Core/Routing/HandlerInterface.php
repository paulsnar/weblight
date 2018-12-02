<?php declare(strict_types=1);

namespace PN\Weblight\Core\Routing;

use PN\Weblight\HTTP\{Request, Response};

interface HandlerInterface
{
  public function handle(Request $rq): Response;
}
