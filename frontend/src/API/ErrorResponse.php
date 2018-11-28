<?php declare(strict_types=1);

namespace PN\Weblight\API;

class ErrorResponse extends Response
{
  public function __construct(int $status, string $message)
  {
    parent::__construct([ 'error' => $message ]);
    $this->status = $status;
  }
}
