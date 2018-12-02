<?php declare(strict_types=1);

namespace PN\Weblight\HTTP;

class Authentication
{
  /** @var string */
  public $username;

  /** @var string|null */
  public $password;

  public static function fromGlobals(): self
  {
    $auth = new static();

    $auth->username = $_SERVER['PHP_AUTH_USER'];
    if ($_SERVER['PHP_AUTH_PW'] !== '') {
      $auth->password = $_SERVER['PHP_AUTH_PW'];
    }

    return $auth;
  }
}
