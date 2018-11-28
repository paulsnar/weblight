<?php declare(strict_types=1);

namespace PN\Weblight\Debugging;

use PN\Weblight\HTTP\Response;

class ErrorResponse extends Response
{
  public function __construct(\Throwable $err)
  {
    $body = "--- Catastrophic Failure, lp0 on fire, printer is not a tty ---\n";
    $body .= "Sorry, an error occured.\n\n";
    $body .= get_class($err) . ': ' . $err->getMessage();
    if ($err->getCode() !== 0) {
      $code = $err->getCode();
      $body .= " (code {$code})";
    }
    $body .= "\n";
    // $body .= sprintf("\n    (%s:%d)\n", $err->getFile(), $err->getLine());

    foreach ($err->getTrace() as $i => $traceLine) {
      if (array_key_exists('class', $traceLine) && $traceLine['class']) {
        $call = $traceLine['class'] . $traceLine['type'] . $traceLine['function'];
      } else {
        $call = $traceLine['function'];
      }
      $body .= sprintf("%2d: %s (%s:%s)\n",
        $i, $call, $traceLine['file'] ?? '??', $traceLine['line'] ?? '??');
    }

    parent::__construct(Response::HTTP_INTERNAL_SERVER_ERROR, [
      'Content-Type' => 'text/plain; charset=UTF-8',
    ], $body);
  }
}
