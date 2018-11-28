<?php declare(strict_types=1);

namespace PN\Weblight\Utilities;

class ImmutableBag extends Bag
{
  public function offsetSet($k, $v)
  {
    throw new \Exception("Cannot set {$k} in immutable Bag");
  }

  public function offsetUnset($k)
  {
    throw new \Exception("Cannot unset {$k} in immutable Bag");
  }
}
