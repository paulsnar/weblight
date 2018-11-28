<?php declare(strict_types=1);

namespace PN\Weblight\Logging;

class SyslogHandler
{
  public function __construct(string $ident = 'wl.frontend')
  {
    if ( ! openlog($ident, \LOG_ODELAY, \LOG_USER)) {
      throw new \Exception('Could not open system log');
    }
  }

  public function __destruct()
  {
    closelog();
  }

  public function process(string $row)
  {
    syslog(\LOG_NOTICE, $row);
  }
}
