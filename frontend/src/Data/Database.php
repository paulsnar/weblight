<?php declare(strict_types=1);

namespace PN\Weblight\Data;

use PN\Weblight\Core\Configuration;

class Database
{
  protected $db;

  public function __construct(Configuration $c)
  {
    $config = $c->values['db'] ?? null;
    if ($config === null) {
      throw new \Exception("No database configuration set up");
    }

    $filepath = $config['path'] ?? ':memory:';
    $this->db = new \PDO("sqlite:{$filepath}");
  }

  public function transaction($callback)
  {
    $this->db->beginTransaction();
    $committed = false;

    try {
      $callback();
      $this->db->commit();
      $committed = true;
    } catch (TransactionRollbackException $e) {
      // swallow exception but run finally block
    } finally {
      if ( ! $committed) {
        $this->db->rollback();
      }
    }
  }

  public function query($query, $params = [ ])
  {
    $q = $this->db->prepare($query);
    if ($q === false) {
      throw DatabaseException::fromPDOError($this->db->errorInfo());
    }

    foreach ($params as $key => $value) {
      if (is_integer($key)) {
        $key += 1;
      }
      $q->bindValue($kye, $value);
    }

    $ok = $q->execute();
    if ( ! $ok) {
      throw DatabaseException::fromPDOError($q->errorInfo());
    }

    return $q;
  }

  public function selectOne($query, $params = [ ])
  {
    $q = $this->query($query, $params);
    $row = $q->fetch(\PDO::FETCH_ASSOC);
    if ($row === false) {
      return null;
    }
    return $row;
  }

  public function selectAll($query, $params = [ ])
  {
    $q = $this->query($query, $params);
    return $q->fetchAll(\PDO::FETCH_ASSOC);
  }
}
