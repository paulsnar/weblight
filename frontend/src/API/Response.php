<?php declare(strict_types=1);

namespace PN\Weblight\API;

use PN\Weblight\HTTP\Response as HTTPResponse;

class Response extends HTTPResponse
{
  public function __construct($data)
  {
    parent::__construct(HTTPResponse::HTTP_OK, [
      'Content-Type' => 'application/json; charset=UTF-8',
    ], json_encode($data, \JSON_UNESCAPED_SLASHES) . "\n");
  }
}
