<?php declare(strict_types=1);

namespace PN\Weblight\Controllers\API;

use PN\Weblight\API\{BaseAPIController, ErrorResponse, NotImplementedException,
  Response as APIResponse};
use PN\Weblight\Core\AppContext;
use PN\Weblight\Data\Models\Program;
use PN\Weblight\Errors\SentinelMismatchException;
use PN\Weblight\HTTP\{Request, Response};
use PN\Weblight\Services\ProgramStorageService;

class ProgramController extends BaseAPIController
{
  /** @var ProgramStorageService */
  protected $programs;

  public function __construct(ProgramStorageService $ps)
  {
    $this->programs = $ps;
  }

  public function getProgramList(Request $rq): Response
  {
    $programs = $this->programs->getProgramStubList();
    return new APIResponse($programs);
  }

  public function createProgram(Request $rq): Response
  {
    $program = $this->programs->createProgram($rq->body);
    unset($program->content);
    return new APIResponse($program);
  }

  public function getProgram(Request $rq): Response
  {
    $revision = $rq->query['revision'] ?? 'latest';

    // @throws NotFoundException
    if ($revision === 'latest') {
      $program = $this->programs->getLatestProgram($rq->arguments['program']);
    } else {
      if ( ! ctype_digit($revision)) {
        return new ErrorResponse(Response::HTTP_BAD_REQUEST,
          'Malformed revision number');
      }

      $revision = intval($revision, 10);
      $program = $this->programs->getProgram(
        $rq->arguments['program'], $revision);
    }

    return new Response(Response::HTTP_OK, [
      'Content-Type' => 'text/x-lua; charset=UTF-8',
      'WL-Revision' => $program->revision,
    ], $program->content);
  }

  public function updateProgram(Request $rq): Response
  {
    $revision = $rq->query['revision'];
    if ($revision === null) {
      return new ErrorResponse(Response::HTTP_BAD_REQUEST,
        'Last revision number must be provided');
    } else if ( ! ctype_digit($revision)) {
      return new ErrorResponse(Response::HTTP_BAD_REQUEST,
        'Malformed revision number');
    }

    $program = new Program;
    $program->ref = $rq->arguments['program'];
    $program->revision = intval($revision, 10);

    try {
      $this->programs->insertNewRevision($program, $rq->body);
    } catch (SentinelMismatchException $e) {
      return new ErrorResponse(Response::HTTP_CONFLICT,
        'Revision number mismatch');
    }

    unset($program->content);
    return new APIResponse($program);
  }

  public function deleteProgram(Request $rq): Response
  {
    // TODO???
    throw new NotImplementedException();
  }
}
