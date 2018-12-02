<?php declare(strict_types=1);

namespace PN\Weblight\Data\Database;

use PDO;

class PDOStub
{
  /** @var PDO|null */
  protected $pdoInstance;

  /** @var string */
  protected $dsn;

  public function __construct(string $dsn)
  {
    $this->dsn = $dsn;
  }

  protected function pdo(): PDO
  {
    if ($this->pdoInstance === null) {
      $this->pdoInstance = new PDO($this->dsn);
    }
    return $this->pdoInstance;
  }

  public function __call(string $name, array $arguments)
  {
    return $this->pdo()->$name(...$arguments);
  }
}
