<?php declare(strict_types=1);

namespace PN\Weblight\Middleware;

use PN\Weblight\Core\{MiddlewareInterface, MiddlewareInterrupt};
use PN\Weblight\HTTP\{DefaultResponses, Request, Response};
use PN\Weblight\Services\AuthService;

class EnsureACLLevel implements MiddlewareInterface
{
  /** @var AuthService */
  protected $auth;

  public function __construct(AuthService $auth)
  {
    $this->auth = $auth;
  }

  public function invoke(Request $rq, ...$arguments): ?Response
  {
    $user = $this->auth->readAuthentication($rq);
    if ($user === null) {
      return Response::redirectModal($rq, '/auth/login');
    }

    if (count($arguments) === 0) {
      return null;
    }

    $aclOptions = array_map(function ($level) use ($user) {
      return ($user->acl->level & $level) === $level;
    }, $arguments);

    $aclSatisfied = array_reduce($aclOptions, function ($c, $i) {
      if ($i === true) {
        return true;
      }
      return $c;
    }, false);

    if ($aclSatisfied) {
      return null;
    }

    return DefaultResponses::forbidden();
  }
}
