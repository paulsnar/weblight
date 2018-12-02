<?php declare(strict_types=1);

namespace PN\Weblight\Services;

use PN\Weblight\Auth\User;
use PN\Weblight\Data\Database;
use PN\Weblight\Errors\NotFoundException;

class UserStorageService
{
  /** @var Database */
  protected $db;

  public function __construct(Database $db)
  {
    $this->db = $db;
  }

  public function fetchUser(int $id): User
  {
    $user = $this->db->selectOne(
      'select * from "users" where "id" = ?', [ $id ]);
    if ($user === null) {
      throw new NotFoundException();
    }

    return User::fromDatabaseRow($user);
  }

  public function findUser(string $username): User
  {
    $user = $this->db->selectOne(
      'select * from "users" where "username" = ?', [ $username ]);
    if ($user === null) {
      throw new NotFoundException();
    }

    return User::fromDatabaseRow($user);
  }

  public function update(User $user)
  {
    $this->db->query(
      'update "users" set "username" = ?, "password" = ?, ' .
        '"is_programmer" = ?, "is_controller" = ?, "is_supereditor" = ? ' .
        'where "id" = ?',
      [ $user->username, $user->passwordHash,
        $user->acl->isProgrammer, $user->acl->isController,
          $user->acl->isSupereditor, $user->id ]);
  }
}
