<?php declare(strict_types=1);

namespace PN\Weblight\Auth;

class User
{
  /** @var int */
  public $id;

  /** @var string */
  public $username, $passwordHash;

  /** @var AccessControlList */
  public $acl;

  public function setHash(string $password)
  {
    $this->passwordHash = password_hash($password, PASSWORD_DEFAULT);
  }

  public function checkPassword(string $attempt)
  {
    if ($this->username === 'root') {
      // nobody can log in as root
      return false;
    }

    return password_verify($attempt, $this->passwordHash);
  }

  public function checkPasswordRehash()
  {
    return password_needs_rehash($this->passwordHash, PASSWORD_DEFAULT);
  }

  public static function fromDatabaseRow(array $row)
  {
    $instance = new static();

    if (array_key_exists('id', $row)) {
      $instance->id = intval($row['id'], 10);
    }
    if (array_key_exists('username', $row)) {
      $instance->username = $row['username'];
    }
    if (array_key_exists('password', $row)) {
      $instance->passwordHash = $row['password'];
    }

    $acl = $instance->acl = new AccessControlList();
    if (array_key_exists('is_programmer', $row)) {
      $acl->isProgrammer = $row['is_programmer'] === '1';
    }
    if (array_key_exists('is_controller', $row)) {
      $acl->isController = $row['is_controller'] === '1';
    }
    if (array_key_exists('is_supereditor', $row)) {
      $acl->isSupereditor = $row['is_supereditor'] === '1';
    }

    return $instance;
  }
}
