<?php declare(strict_types=1);

namespace PN\Weblight\Curl;

use PN\Weblight\HTTP\HeaderBag;

class Response
{
  public $status, $headers, $body;

  public function __construct(int $status, array $headers, string $body)
  {
    $this->status = $status;
    $this->headers = $headers;
    $this->body = $body;
  }
}
