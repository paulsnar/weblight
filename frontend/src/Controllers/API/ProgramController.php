<?php declare(strict_types=1);

namespace PN\Weblight\Controllers\API;

use PN\Weblight\API\{BaseAPIController, ErrorResponse, NotImplementedException, Response};
use PN\Weblight\Data\Models\Program;
use PN\Weblight\Errors\SentinelMismatchException;
use PN\Weblight\HTTP\{Request, Response as HTTPResponse};
use PN\Weblight\Services\ProgramStorageService;

class ProgramController extends BaseAPIController
{
  protected $programs;

  public function __construct(ProgramStorageService $ps)
  {
    $this->programs = $ps;
  }

  public function getProgramList(Request $rq)
  {
    $programs = $this->programs->getProgramStubList();
    return new Response($programs);
  }

  public function createProgram(Request $rq)
  {
    $program = $this->programs->createProgram($rq->body);
    unset($program->content);
    return new Response($program);
  }

  public function getProgram(Request $rq)
  {
    $revision = $rq->query['revision'] ?? 'latest';

    // @throws NotFoundException
    if ($revision === 'latest') {
      $program = $this->programs->getLatestProgram($rq->arguments['program']);
    } else {
      if ( ! ctype_digit($revision)) {
        return new ErrorResponse(HTTPResponse::HTTP_BAD_REQUEST,
          'Malformed revision number');
      }

      $revision = intval($revision, 10);
      $program = $this->programs->getProgram(
        $rq->arguments['program'], $revision);
    }

    return new HTTPResponse(HTTPResponse::HTTP_OK, [
      'Content-Type' => 'text/x-lua; charset=UTF-8',
      'WL-Revision' => $program->revision,
    ], $program->content);
  }

  public function updateProgram(Request $rq)
  {
    $revision = $rq->query['revision'];
    if ($revision === null) {
      return new ErrorResponse(HTTPResponse::HTTP_BAD_REQUEST,
        'Last revision number must be provided');
    } else if ( ! ctype_digit($revision)) {
      return new ErrorResponse(HTTPResponse::HTTP_BAD_REQUEST,
        'Malformed revision number');
    }

    $program = new Program;
    $program->ref = $rq->arguments['program'];
    $program->revision = intval($revision, 10);

    try {
      $this->programs->insertNewRevision($program, $rq->body);
    } catch (SentinelMismatchException $e) {
      return new ErrorResponse(HTTPResponse::HTTP_CONFLICT,
        'Revision number mismatch');
    }

    unset($program->content);
    return new Response($program);
  }

  public function deleteProgram(Request $rq)
  {
    // TODO???
    throw new NotImplementedException();
  }
}
