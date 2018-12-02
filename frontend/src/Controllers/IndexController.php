<?php declare(strict_types=1);

namespace PN\Weblight\Controllers;

use PN\Weblight\Core\{AppContext, BaseController};
use PN\Weblight\HTTP\{Request, Response};
use PN\Weblight\Middleware\EnsureAuthenticated;
use PN\Weblight\Views\Environment;
use PN\Weblight\Services\{ProgramStorageService, StrandEventPusherService};

class IndexController extends BaseController
{
  /** @var ProgramStorageService */
  protected $programs;
  /** @var StrandEventPusherService */
  protected $strandEvents;
  /** @var Environment */
  protected $views;

  public function __construct(
    ProgramStorageService $pss,
    StrandEventPusherService $stev,
    Environment $env
  ) {
    $this->programs = $pss;
    $this->strandEvents = $stev;
    $this->views = $env;
  }

  public function frontpage(AppContext $ctx, Request $rq): Response
  {
    $this->requireMiddleware($rq, EnsureAuthenticated::class);

    $connected = $this->strandEvents->isConnected();
    $programs = $this->programs->getProgramStubList();

    return $this->views->renderResponse('index.html', [
      'connected' => $connected,
      'programs' => $programs,
    ]);
  }
}
