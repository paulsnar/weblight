<?php declare(strict_types=1);

namespace PN\Weblight\Controllers\API;

use PN\Weblight\API\{BaseAPIController, ErrorResponse, NotImplementedException};
use PN\Weblight\Core\AppContext;
use PN\Weblight\Events\EventSubmissionError;
use PN\Weblight\Logging\LogRouter;
use PN\Weblight\HTTP\{Request as HTTPRequest, Response as HTTPResponse};
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

  public function deployProgram(HTTPRequest $rq): Response
  {
    $ref = $rq->form['id'] ?? null;
    if ($ref === null) {
      return new ErrorResponse(HTTPResponse::HTTP_BAD_REQUEST, 'No program specified');
    }

    $revision = $rq->form['revision'] ?? null;
    if ($revision !== null) {
      if ( ! ctype_digit($revision)) {
        return new ErrorResponse(HTTPResponse::HTTP_BAD_REQUEST, 'Malformed revision number');
      }
      $revision = intval($revision, 10);
    }

    try {
      $this->strand->deployProgram($ref, $revision);
      return new HTTPResponse(HTTPResponse::HTTP_CREATED);
    } catch (EventSubmissionError $e) {
      $this->log->dispatch([
        'type' => 'event-failure', 'result' => $e->getMessage() ]);
      return new ErrorResponse(HTTPResponse::HTTP_INTERNAL_SERVER_ERROR, 'Internal error');
    }
  }

  public function redeployPreviousProgram(HTTPRequest $rq): Response
  {
    try {
      $this->strand->redeployLastProgram();
      return new HTTPResponse(HTTPResponse::HTTP_ACCEPTED);
    } catch (EventSubmissionError $e) {
      $this->log->dispatch([
        'type' => 'event-failure', 'result' => $e->getMessage() ]);
      return new ErrorResponse(HTTPResponse::HTTP_INTERNAL_SERVER_ERROR, 'Internal error');
    }
  }

  public function powerOff(HTTPRequest $rq): Response
  {
    try {
      $this->strand->powerOff();
      return new HTTPResponse(HTTPResponse::HTTP_ACCEPTED);
    } catch (EventSubmissionError $e) {
      $this->log->dispatch([
        'type' => 'event-failure', 'result' => $e->getMessage() ]);
      return new ErrorResponse(HTTPResponse::HTTP_INTERNAL_SERVER_ERROR, 'Internal error');
    }
  }
}
