<?php declare(strict_types=1);

namespace PN\Weblight\Middleware;

use PN\Weblight\Core\{MiddlewareInterface, MiddlewareInterrupt};
use PN\Weblight\HTTP\{DefaultResponses, Request, Response};
use PN\Weblight\Services\AuthService;

class EnsureIsController implements MiddlewareInterface
{
  /** @var AuthService */
  protected $auth;

  public function __construct(AuthService $auth)
  {
    $this->auth = $auth;
  }

  public function invoke(Request $rq): ?Response
  {
    $user = $this->auth->readAuthentication($rq);
    if ($user === null) {
      return Response::redirectModal($rq, '/auth/login');
    }

    if ($user->acl->isController !== true) {
      return DefaultResponses::forbidden();
    }

    return null;
  }
}
