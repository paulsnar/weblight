<?php declare(strict_types=1);

namespace PN\Weblight\API;

use PN\Weblight\HTTP\{HTTPSerializable, Request, Response};

class NotImplementedException extends \Exception implements HTTPSerializable
{
  public function httpSerialize(Request $rq): Response
  {
    return new ErrorResponse(Response::HTTP_NOT_IMPLEMENTED, 'Not implemented');
  }
}
