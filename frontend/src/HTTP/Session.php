<?php declare(strict_types=1);

namespace PN\Weblight\HTTP;

use PN\Weblight\Utilities\Bag;

class Session extends Bag
{
  public function __construct()
  {
    $this->bag = null;
  }

  protected function start()
  {
    session_start([
      'name' => 'weblight_session',
    ]);
    $this->bag =& $_SESSION;
  }

  public function clear()
  {
    if ($this->bag === null) {
      $this->start();
    }
    session_unset();
  }

  public function offsetExists($k)
  {
    if ($this->bag === null) {
      $this->start();
    }

    return parent::offsetExists($k);
  }

  public function offsetGet($k)
  {
    if ($this->bag === null) {
      $this->start();
    }

    return parent::offsetGet($k);
  }

  public function offsetSet($k, $v)
  {
    if ($this->bag === null) {
      $this->start();
    }

    return parent::offsetSet($k, $v);
  }

  public function offsetUnset($k)
  {
    if ($this->bag === null) {
      $this->start();
    }

    return parent::offsetUnset($k);
  }

  public function rewind()
  {
    if ($this->bag === null) {
      $this->start();
    }

    return parent::rewind();
  }
}