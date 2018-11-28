<?php declare(strict_types=1);

namespace PN\Weblight\HTTP;

use PN\Weblight\Utilities\Bag;

class HeaderBag extends Bag
{
  public function __construct($headers)
  {
    $hds = [ ];
    foreach ($headers as $name => $value) {
      $hds[strtolower($name)] = $value;
    }
    parent::__construct($hds);
  }

  public function offsetExists($k)
    { return parent::offsetExists(strtolower($k)); }

  public function offsetGet($k)
    { return parent::offsetGet(strtolower($k)); }

  public function offsetSet($k, $v)
    { return parent::offsetSet(strtolower($k), $v); }

  public function offsetUnset($k)
    { return parent::offsetUnset(strtolower($k)); }

  public static function fromGlobals()
  {
    $hds = [ ];
    foreach ($_SERVER as $key => $value) {
      if (strpos($key, 'HTTP_') === 0) {
        $key = substr($key, 5);
        $key = str_replace('_', '-', $key);
        $hds[$key] = $value;
      }
    }
    return new static($hds);
  }
}
