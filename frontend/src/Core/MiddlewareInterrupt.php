<?php declare(strict_types=1);

namespace PN\Weblight\Core;

use PN\Weblight\HTTP\Response;

class MiddlewareInterrupt extends \Exception
{
  /** @var Response */
  public $response;

  public function __construct(Response $resp)
  {
    $this->response = $resp;
  }
}