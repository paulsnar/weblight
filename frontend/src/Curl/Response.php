<?php declare(strict_types=1);

namespace PN\Weblight\Curl;

use PN\Weblight\HTTP\HeaderBag;

class Response
{
  /** @var int */
  public $status;

  /** @var string[] */
  public $headers;

  /** @var string */
  public $body;

  public function __construct(int $status, array $headers, string $body)
  {
    $this->status = $status;
    $this->headers = $headers;
    $this->body = $body;
  }
}
