<?php declare(strict_types=1);

namespace PN\Weblight\Logging;

use PN\Weblight\Core\Configuration;

class LogRouter
{
  protected $handler, $trace = false;

  public function __construct(Configuration $config)
  {
    $config = $config->values['log'] ?? [ 'target' => 'syslog' ];

    switch ($config['target']) {
      case 'syslog':
        $this->handler = new SyslogHandler();
        break;

      case 'stderr':
        $this->handler = new StderrHandler();
        break;

      default:
        throw new \Exception("Unknown log target: {$config['target']}");
    }

    if (array_key_exists('trace', $config)) {
      $this->trace = $config['trace'];
    }
  }

  public function dispatch(array $entry)
  {
    $msg = [ 'at' => time() ];

    if (array_key_exists('exception', $entry)) {
      $exc = $entry['exception'];
      $msg['type'] = 'exception';
      $msg['class'] = get_class($exc);
      $msg['text'] = $exc->getMessage();
      if (($file = $exc->getFile())) {
        $msg['file'] = $file;
      }
      if (($line = $exc->getLine())) {
        $msg['line'] = $line;
      }

      if ($this->trace) {
        $msg['trace'] = $exc->getTraceAsString();
      }
    } else {
      $msg += $entry;
    }

    $row = Logfmt::encode($msg);
    $this->handler->process($row);
  }
}
