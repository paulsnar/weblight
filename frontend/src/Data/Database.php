<?php declare(strict_types=1);

namespace PN\Weblight\Data;

use PN\Weblight\Core\Configuration;
use PN\Weblight\Data\Database\PDOStub;
use PDO, PDOStatement;

class Database
{
  /** @var PDOStub|\PDO */
  protected $db;

  public function __construct(Configuration $c)
  {
    $config = $c->values['db'] ?? null;
    if ($config === null) {
      throw new \Exception("No database configuration set up");
    }

    $filepath = $config['path'] ?? ':memory:';
    $this->db = new PDOStub("sqlite:{$filepath}");
  }

  /** @return string|int */
  public function lastInsertId()
  {
    $id = $this->db->lastInsertId();
    if (ctype_digit($id)) {
      return intval($id, 10);
    }
    return $id;
  }

  public function transaction(callable $callback)
  {
    $this->db->beginTransaction();
    $committed = false;

    try {
      $result = $callback();
      $this->db->commit();
      $committed = true;
    } catch (TransactionRollbackException $e) {
      // swallow exception but run finally block
    } finally {
      if ( ! $committed) {
        $this->db->rollback();
      }
    }

    return $result;
  }

  /** @throws DatabaseException */
  public function query(string $query, array $params = [ ]): PDOStatement
  {
    $q = $this->db->prepare($query);
    if ($q === false) {
      throw DatabaseException::fromPDOError($this->db->errorInfo());
    }

    foreach ($params as $key => $value) {
      if (is_integer($key)) {
        $key += 1;
      }
      $type = \PDO::PARAM_STR;
      if (is_integer($value)) {
        $type = \PDO::PARAM_INT;
      } else if (is_bool($value)) {
        $type = \PDO::PARAM_BOOL;
        $value = $value ? '1' : '0';
      } else if ($value === null) {
        $type = \PDO::PARAM_NULL;
      }
      $q->bindValue($key, $value, $type);
    }

    $ok = $q->execute();
    if ( ! $ok) {
      throw DatabaseException::fromPDOError($q->errorInfo());
    }

    return $q;
  }

  /** @throws DatabaseException */
  public function selectOne(string $query, array $params = [ ]): ?array
  {
    $q = $this->query($query, $params);
    $row = $q->fetch(\PDO::FETCH_ASSOC);
    if ($row === false) {
      return null;
    }
    return $row;
  }

  /** @throws DatabaseException */
  public function selectAll(string $query, array $params = [ ]): array
  {
    $q = $this->query($query, $params);
    return $q->fetchAll(\PDO::FETCH_ASSOC);
  }
}
