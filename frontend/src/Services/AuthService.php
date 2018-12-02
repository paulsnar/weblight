<?php declare(strict_types=1);

namespace PN\Weblight\Services;

use PN\Weblight\Auth\{AuthenticationAttemptFailure, User};
use PN\Weblight\Errors\NotFoundException;
use PN\Weblight\HTTP\Request;

class AuthService
{
  /** @var UserStorageService */
  protected $users;

  public function __construct(UserStorageService $uss)
  {
    $this->users = $uss;
  }

  public function readAuthentication(Request $rq): ?User
  {
    $id = $rq->session['auth.user_id'] ?? null;
    if ($id === null) {
      return null;
    }

    try {
      $user = $this->users->fetchUser($id);
    } catch (NotFoundException $e) {
      unset($rq->session['auth.user_id']);
      return null;
    }

    $rq->properties['auth.user'] = $user;
    return $user;
  }

  public function tryAuthentication(Request $rq, string $username, string $password): User
  {
    try {
      $user = $this->users->findUser($username);
    } catch (NotFoundException $e) {
      throw new AuthenticationAttemptFailure('User not found in database',
        null, $username);
    }

    if ( ! $user->checkPassword($password)) {
      throw new AuthenticationAttemptFailure('Password mismatch',
        null, $username);
    }

    if ($user->checkPasswordRehash()) {
      $user->setHash($password);
      $this->users->update($user);
    }

    $rq->session['auth.user_id'] = $user->id;
    $rq->properties['auth.user'] = $user;

    return $user;
  }

  public function logOut(Request $rq)
  {
    unset($rq->session['auth.user_id']);
    if (array_key_exists('auth.user', $rq->properties)) {
      unset($rq->properties['auth.user']);
    }
  }
}
