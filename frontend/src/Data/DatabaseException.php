<?php declare(strict_types=1);

namespace PN\Weblight\Data;

class DatabaseException extends \Exception
{
  public static function fromPDOError(array $errorInfo)
  {
    [ $sqlstate, $code, $msg ] = $errorInfo;
    return new static("SQLSTATE {$sqlstate}: {$msg} ({$code})");
  }
}
