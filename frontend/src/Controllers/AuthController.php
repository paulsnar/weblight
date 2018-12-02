<?php declare(strict_types=1);

namespace PN\Weblight\Controllers;

use PN\Weblight\Auth\AuthenticationAttemptFailure;
use PN\Weblight\Core\{AppContext, BaseController};
use PN\Weblight\HTTP\{Request, Response};
use PN\Weblight\Services\AuthService;
use PN\Weblight\Views\Environment;

class AuthController extends BaseController
{
  /** @var AuthService */
  protected $auth;

  /** @var Environment */
  protected $views;

  public function __construct(AuthService $auth, Environment $env)
  {
    $this->auth = $auth;
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
}
