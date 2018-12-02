<?php declare(strict_types=1);

namespace PN\Weblight\Utilities;

class Bag implements \ArrayAccess, \Iterator
{
  /** @var array */
  protected $bag;

  /** @var \Iterator|null */
  protected $iterator;

  public function __construct(array $initial = [ ])
  {
    $this->bag = $initial;
  }

  public function offsetExists($k)
  {
    return array_key_exists($k, $this->bag);
  }

  public function offsetGet($k)
  {
    return $this->bag[$k] ?? null;
  }

  public function offsetSet($k, $v)
  {
    $this->bag[$k] = $v;
  }

  public function offsetUnset($k)
  {
    if (array_key_exists($k, $this->bag)) {
      unset($this->bag[$k]);
    }
  }

  public function toArray()
  {
    return $this->bag;
  }

  public function rewind()
  {
    $this->iterator = (function () {
      $b = $this->bag;
      foreach ($b as $key => $value) {
        yield $key => $value;
      }
    })();
    return $this->iterator->rewind();
  }

  public function key()
  {
    if ($this->iterator !== null) {
      return $this->iterator->key();
    }
  }

  public function current()
  {
    if ($this->iterator !== null) {
      return $this->iterator->current();
    }
  }

  public function next()
  {
    if ($this->iterator !== null) {
      return $this->iterator->next();
    }
  }

  public function valid()
  {
    if ($this->iterator !== null) {
      return $this->iterator->valid();
    }

    return false;
  }
}
