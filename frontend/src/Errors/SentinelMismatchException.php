<?php declare(strict_types=1);

namespace PN\Weblight\Errors;

class SentinelMismatchException extends \Exception
{
  public $expectedValue;

  public function __construct($expected)
  {
    $this->expectedValue = $expected;
    parent::__construct();
  }
}
