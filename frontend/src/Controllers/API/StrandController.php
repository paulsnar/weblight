<?php declare(strict_types=1);

namespace PN\Weblight\Controllers\API;

use PN\Weblight\API\{BaseAPIController, ErrorResponse, NotImplementedException};
use PN\Weblight\Core\AppContext;
use PN\Weblight\Curl\{Request as CurlRequest, Session as CurlSession};
use PN\Weblight\HTTP\{Request as HTTPRequest, Response as HTTPResponse};
use PN\Weblight\Services\ProgramStorageService;

class StrandController extends BaseAPIController
{
  protected $ch, $programs;

  public function __construct(CurlSession $ch, ProgramStorageService $ps)
  {
    $this->ch = $ch;
    $this->programs = $ps;
  }

  public function deployProgram(AppContext $ctx, HTTPRequest $rq)
  {
    $ref = $rq->form['id'] ?? null;
    if ($ref === null) {
      return new ErrorResponse(HTTPResponse::HTTP_BAD_REQUEST, 'No program specified');
    }

    $revision = $rq->form['revision'] ?? null;
    if ($revision === null) {
      $program = $this->programs->getLatestProgram($ref);
    } else {
      if ( ! ctype_digit($revision)) {
        return new ErrorResponse(HTTPResponse::HTTP_BAD_REQUEST, 'Malformed revision number');
      }
      $revision = intval($revision, 10);
      $program = $this->programs->getProgram($ref, $revision);
    }

    $rq = CurlRequest::post('http://127.0.14.1:8000/submit', json_encode([
      'event' => 'reprogram',
      'data' => $program->content,
    ], \JSON_UNESCAPED_SLASHES));
    $resp = $this->ch->perform($rq);
    if ($resp->status !== HTTPResponse::HTTP_CREATED) {
      $ctx->log([ 'type' => 'event-failure', 'result' => $resp->body ]);
      return new ErrorResponse(HTTPResponse::HTTP_INTERNAL_SERVER_ERROR, 'Internal error');;
    }

    return new HTTPResponse(HTTPResponse::HTTP_CREATED);
  }
}
