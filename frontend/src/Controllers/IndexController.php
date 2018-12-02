<?php declare(strict_types=1);

namespace PN\Weblight\Controllers;

use PN\Weblight\Core\{AppContext, BaseController};
use PN\Weblight\HTTP\{Request, Response};
use PN\Weblight\Middleware\EnsureACLLevel;
use PN\Weblight\Views\Environment;
use PN\Weblight\Services\{AuthService, ProgramStorageService, StrandEventPusherService};

class IndexController extends BaseController
{
  /** @var AuthService */
  protected $auth;

  /** @var ProgramStorageService */
  protected $programs;

  /** @var StrandEventPusherService */
  protected $strandEvents;

  /** @var Environment */
  protected $views;

  public function __construct(
    AuthService $auth,
    ProgramStorageService $pss,
    StrandEventPusherService $stev,
    Environment $env
  ) {
    $this->auth = $auth;
    $this->programs = $pss;
    $this->strandEvents = $stev;
    $this->views = $env;
  }

  public function frontpage(Request $rq): Response
  {
    $this->requireMiddleware($rq, EnsureACLLevel::class);

    $user = $this->auth->readAuthentication($rq);
    $connected = $this->strandEvents->isConnected();
    $programs = $this->programs->getProgramStubList();

    return $this->views->renderResponse('index.html', [
      'can_control' => $user->acl->isController(),
      'connected' => $connected,
      'programs' => $programs,
    ]);
  }
}
