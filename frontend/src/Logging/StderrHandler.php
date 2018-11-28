<?php declare(strict_types=1);

namespace PN\Weblight\Logging;

class StderrHandler
{
  protected $stderr;

  public function __construct()
  {
    $this->stderr = fopen('php://stderr', 'w');
  }

  public function __destruct()
  {
    fclose($this->stderr);
  }

  public function process(string $row)
  {
    fwrite($this->stderr, $row . PHP_EOL);
  }
}
