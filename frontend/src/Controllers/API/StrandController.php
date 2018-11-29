<?php declare(strict_types=1);

namespace PN\Weblight\Controllers\API;

use PN\Weblight\API\{BaseAPIController, ErrorResponse, NotImplementedException};
use PN\Weblight\Core\AppContext;
use PN\Weblight\Curl\{Request as CurlRequest, Session as CurlSession};
use PN\Weblight\HTTP\{Request as HTTPRequest, Response as HTTPResponse};
use PN\Weblight\Services\StrandService;

class StrandController extends BaseAPIController
{
  protected $ch, $strand;

  public function __construct(CurlSession $ch, StrandService $strand)
  {
    $this->ch = $ch;
    $this->strand = $strand;
  }

  public function deployProgram(AppContext $ctx, HTTPRequest $rq)
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
    } catch (\Throwable $e) {
      $ctx->log([ 'type' => 'event-failure', 'result' => $e->getMessage() ]);
      return new ErrorResponse(HTTPResponse::HTTP_INTERNAL_SERVER_ERROR, 'Internal error');
    }
  }

  public function powerOff(AppContext $ctx, HTTPRequest $rq)
  {
    try {
      $this->strand->powerOff();
      return new HTTPResponse(HTTPResponse::HTTP_ACCEPTED);
    } catch (\Throwable $e) {
      $ctx->log([ 'type' => 'event-failure', 'result' => $e->getMessage() ]);
      return new ErrorResponse(HTTPResponse::HTTP_INTERNAL_SERVER_ERROR, 'Internal error');
    }
  }
}
