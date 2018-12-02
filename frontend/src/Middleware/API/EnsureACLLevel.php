<?php declare(strict_types=1);

namespace PN\Weblight\Middleware\API;

use PN\Weblight\API\ErrorResponse;
use PN\Weblight\Auth\AuthenticationAttemptFailure;
use PN\Weblight\Core\{MiddlewareInterface, MiddlewareInterrupt};
use PN\Weblight\HTTP\{Request, Response};
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

    if ($user === null && $rq->authentication !== null) {
      try {
        $user = $this->auth->tryAuthentication($rq,
          $rq->authentication->username, $rq->authentication->password ?: '');
      } catch (AuthenticationAttemptFailure $e) {
        // pass
      }
    }

    if ($user === null) {
      return new ErrorResponse(Response::HTTP_UNAUTHORIZED,
        'Missing authentication credentials');
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

    return new ErrorResponse(Response::HTTP_FORBIDDEN,
      'Current user doesn\'t posess the necessary authorization to do that');
  }
}
