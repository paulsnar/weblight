<?php declare(strict_types=1);

namespace PN\Weblight\Controllers;

use PN\Weblight\Core\{AppContext, BaseController};
use PN\Weblight\HTTP\{Request, Response};
use PN\Weblight\Views\Environment;
use PN\Weblight\Services\{ProgramStorageService, StrandEventPusherService};

class IndexController extends BaseController
{
  public function frontpage(AppContext $ctx, Request $rq)
  {
    $ev = $ctx->get(StrandEventPusherService::class);
    $connected = $ev->isConnected();

    $programService = $ctx->get(ProgramStorageService::class);
    $programs = $programService->getProgramStubList();

    $tplenv = $ctx->get(Environment::class);
    return $tplenv->renderResponse('index.html', [
      'connected' => $connected,
      'programs' => $programs,
    ]);
  }
}
