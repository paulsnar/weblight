<?php declare(strict_types=1);

namespace PN\Weblight\Middleware;

use PN\Weblight\Core\{AppContext, MiddlewareInterface};
use PN\Weblight\HTTP\{Request, Response};
use PN\Weblight\Services\AuthService;
use PN\Weblight\Core\MiddlewareInterrupt;

class EnsureAuthenticated implements MiddlewareInterface
{
  /** @var AuthService */
  protected $auth;

  public function __construct(AuthService $auth)
  {
    $this->auth = $auth;
  }

  public function invoke(AppContext $ctx, Request $rq): ?Response
  {
    if ( ! $this->auth->isAuthenticated($rq)) {
      return Response::redirectModal($rq, '/auth/login');
    }

    return null;
  }
}
