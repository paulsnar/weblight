<?php declare(strict_types=1);

namespace PN\Weblight\Controllers;

use PN\Weblight\Auth\AuthenticationAttemptFailure;
use PN\Weblight\Core\{AppContext, BaseController};
use PN\Weblight\HTTP\{Request, Response};
use PN\Weblight\Middleware\EnsureACLLevel;
use PN\Weblight\Services\{AuthService, UserStorageService};
use PN\Weblight\Views\Environment;

class AuthController extends BaseController
{
  /** @var AuthService */
  protected $auth;

  /** @var UserStorageService */
  protected $users;

  /** @var Environment */
  protected $views;

  public function __construct(
    AuthService $auth,
    UserStorageService $uss,
    Environment $env
  ) {
    $this->auth = $auth;
    $this->users = $uss;
    $this->views = $env;
  }

  public function login(Request $rq): Response
  {
    if ($this->auth->readAuthentication($rq) !== null) {
      return Response::redirectIntended($rq, '/');
    }

    $ctx = [ ];
    if (isset($rq->query['then'])) {
      $ctx['then'] = $rq->query['then'];
    }

    try {
      if ($rq->authentication !== null ||
          isset($rq->query['credentials']) ||
          $rq->method === 'POST') {
        return $this->handleLoginAttempt($rq);
      }
    } catch (AuthenticationAttemptFailure $e) {
      $ctx['error'] = $e->publicMessage ?: 'Neizdevās ielogoties.';
      if ($e->previousUsername !== null) {
        $ctx['username'] = $e->previousUsername;
      }
      return $this->views->renderResponse(
        'auth/login.html', $ctx, Response::HTTP_UNAUTHORIZED);
    }

    return $this->views->renderResponse('auth/login.html', $ctx);
  }

  protected function handleLoginAttempt(Request $rq): Response
  {
    if (isset($rq->query['credentials'])) {
      $tuple = base64_decode($rq->query['credentials']);
      [ $username, $password ] = explode(':', $tuple, 2);
    } else if ($rq->authentication !== null) {
      $auth = $rq->authentication;
      $username = $auth->username;
      $password = $auth->password;
    } else if ($rq->method === 'POST') {
      $username = $rq->form['username'];
      $password = $rq->form['password'];
    } else {
      throw new AuthenticationAttemptFailure('Missing credentials');
    }

    $this->auth->tryAuthentication($rq, $username, $password);
    return Response::redirectIntended($rq, '/');
  }

  protected function handleExplicitLogin(Request $rq): Response
  {
    $username = $rq->form['username'];
    $password = $rq->form['password'];
    $this->auth->tryAuthentication($rq, $username, $password);

    return Response::redirectIntended($rq, '/');
  }

  public function logout(Request $rq): Response
  {
    if ($this->auth->readAuthentication($rq) === null) {
      return Response::redirectIntended($rq, '/');
    }

    $this->auth->logOut($rq);
    return Response::redirectIntended($rq, '/');
  }

  public function showPasswordChangeScreen(Request $rq): Response
  {
    $this->requireMiddleware($rq, EnsureACLLevel::class);
    return $this->views->renderResponse('auth/changepw.html');
  }

  public function changePassword(Request $rq): Response
  {
    $this->requireMiddleware($rq, EnsureACLLevel::class);

    $user = $this->auth->readAuthentication($rq);

    if ($rq->form['new-password-1'] !== $rq->form['new-password-2']) {
      return $this->views->renderResponse('auth/changepw.html', [
        'error' => 'Jaunās paroles nesakrīt.',
      ]);
    }

    $pw = $rq->form['password'];
    if ( ! $user->checkPassword($pw)) {
      return $this->views->renderResponse('auth/changepw.html', [
        'error' => 'Pašreizējā parole nebija pareiza.',
      ]);
    }

    $user->setHash($rq->form['new-password-1']);
    $this->users->update($user);

    return Response::redirectTo('/');
  }
}
