<?php declare(strict_types=1);

namespace PN\Weblight\Data\Database;

class PDOStub
{
  protected $pdoInstance, $dsn;

  public function __construct(string $dsn)
  {
    $this->dsn = $dsn;
  }

  protected function pdo()
  {
    if ($this->pdoInstance === null) {
      $this->pdoInstance = new \PDO($this->dsn);
    }
    return $this->pdoInstance;
  }

  public function __call(string $name, array $arguments)
  {
    return $this->pdo()->$name(...$arguments);
  }
}
