<?php declare(strict_types=1);

namespace PN\Weblight\Core;

use PN\Weblight\Core\AppContext;
use PN\Weblight\HTTP\{Request, Response};

class BaseController
{
  public function invoke(AppContext $ctx, string $method, Request $request): Response
  {
    return $this->$method($request);
  }
}
