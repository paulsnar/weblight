<?php declare(strict_types=1);

namespace PN\Weblight\Controllers;

use PN\Weblight\Core\{AppContext, BaseController};
use PN\Weblight\Errors\NotFoundException;
use PN\Weblight\HTTP\{Request, Response};
use PN\Weblight\Middleware\EnsureAuthenticated;
use PN\Weblight\Services\ProgramStorageService;
use PN\Weblight\Views\Environment;

class ProgramController extends BaseController
{
  /** @var ProgramStorageService */
  protected $programs;

  /** @var Environment */
  protected $views;

  public function __construct(ProgramStorageService $pss, Environment $env)
  {
    $this->programs = $pss;
    $this->views = $env;
  }

  public function listPrograms(AppContext $ctx, Request $rq): Response
  {
    $this->requireMiddleware($rq, EnsureAuthenticated::class);

    $programs = $this->programs->getProgramStubList();
    return $this->views->renderResponse('programs/list.html', [
      'programs' => $programs,
    ]);
  }

  public function showProgram(AppContext $ctx, Request $rq): Response
  {
    $ref = $rq->arguments['program'];
    if (isset($rq->query['revision']) && ctype_digit($rq->query['revision'])) {
      $rev = intval($rq->query['revision'], 10);
      $program = $this->programs->getProgram($ref, $rev);
    } else {
      $program = $this->programs->getLatestProgram($ref);
    }
    if ($program === null) {
      throw new NotFoundException();
    }

    return $this->views->renderResponse('programs/show.html', [
      'program' => $program,
    ]);
  }
}
