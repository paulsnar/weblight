<?php declare(strict_types=1);

namespace PN\Weblight\Controllers\API;

use PN\Weblight\API\{BaseAPIController, ErrorResponse, NotImplementedException};
use PN\Weblight\Auth\AccessControlList;
use PN\Weblight\Core\AppContext;
use PN\Weblight\Events\EventSubmissionError;
use PN\Weblight\Logging\LogRouter;
use PN\Weblight\Middleware\API\EnsureACLLevel;
use PN\Weblight\HTTP\{Request, Response};
use PN\Weblight\Services\StrandService;

class StrandController extends BaseAPIController
{
  /** @var StrandService */
  protected $strand;

  /** @var LogRouter */
  protected $log;

  public function __construct(StrandService $strand, LogRouter $log)
  {
    $this->strand = $strand;
    $this->log = $log;
  }

  public function deployProgram(Request $rq): Response
  {
    $this->requireMiddleware($rq, EnsureACLLevel::class,
      AccessControlList::IS_CONTROLLER);

    $ref = $rq->form['id'] ?? null;
    if ($ref === null) {
      return new ErrorResponse(Response::HTTP_BAD_REQUEST, 'No program specified');
    }

    $revision = $rq->form['revision'] ?? null;
    if ($revision !== null) {
      if ( ! ctype_digit($revision)) {
        return new ErrorResponse(Response::HTTP_BAD_REQUEST, 'Malformed revision number');
      }
      $revision = intval($revision, 10);
    }

    try {
      $this->strand->deployProgram($ref, $revision);
      return new Response(Response::HTTP_ACCEPTED);
    } catch (EventSubmissionError $e) {
      $this->log->dispatch([
        'type' => 'event-failure', 'result' => $e->getMessage() ]);
      return new ErrorResponse(Response::HTTP_INTERNAL_SERVER_ERROR, 'Internal error');
    }
  }

  public function redeployPreviousProgram(Request $rq): Response
  {
    $this->requireMiddleware($rq, EnsureACLLevel::class,
      AccessControlList::IS_CONTROLLER);

    try {
      $this->strand->redeployLastProgram();
      return new Response(Response::HTTP_ACCEPTED);
    } catch (EventSubmissionError $e) {
      $this->log->dispatch([
        'type' => 'event-failure', 'result' => $e->getMessage() ]);
      return new ErrorResponse(Response::HTTP_INTERNAL_SERVER_ERROR, 'Internal error');
    }
  }

  public function powerOff(Request $rq): Response
  {
    $this->requireMiddleware($rq, EnsureACLLevel::class,
      AccessControlList::IS_CONTROLLER);

    try {
      $this->strand->powerOff();
      return new Response(Response::HTTP_ACCEPTED);
    } catch (EventSubmissionError $e) {
      $this->log->dispatch([
        'type' => 'event-failure', 'result' => $e->getMessage() ]);
      return new ErrorResponse(Response::HTTP_INTERNAL_SERVER_ERROR, 'Internal error');
    }
  }
}
