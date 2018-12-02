<?php declare(strict_types=1);

namespace PN\Weblight\Controllers\API;

use PN\Weblight\API\{BaseAPIController, ErrorResponse, NotImplementedException,
  Response as APIResponse};
use PN\Weblight\Auth\AccessControlList;
use PN\Weblight\Core\AppContext;
use PN\Weblight\Data\Models\Program;
use PN\Weblight\Errors\SentinelMismatchException;
use PN\Weblight\HTTP\{Request, Response};
use PN\Weblight\Middleware\API\EnsureACLLevel;
use PN\Weblight\Services\{AuthService, ProgramStorageService};

class ProgramController extends BaseAPIController
{
  /** @var AuthService */
  protected $auth;

  /** @var ProgramStorageService */
  protected $programs;

  public function __construct(AuthService $auth, ProgramStorageService $ps)
  {
    $this->auth = $auth;
    $this->programs = $ps;
  }

  public function getProgramList(Request $rq): Response
  {
    $this->requireMiddleware($rq, EnsureACLLevel::class);

    $programs = $this->programs->getProgramStubList();
    return new APIResponse($programs);
  }

  public function createProgram(Request $rq): Response
  {
    $this->requireMiddleware($rq, EnsureACLLevel::class,
      AccessControlList::IS_PROGRAMMER);

    $user = $this->auth->readAuthentication($rq);

    $program = $this->programs->createProgram($user, $rq->body);
    unset($program->content);
    return new APIResponse($program);
  }

  public function getProgram(Request $rq): Response
  {
    $revision = $rq->query['revision'] ?? 'latest';

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
    $this->requireMiddleware($rq, EnsureACLLevel::class,
      AccessControlList::IS_PROGRAMMER, AccessControlList::IS_SUPEREDITOR);

    $user = $this->auth->readAuthentication($rq);

    $revision = $rq->query['revision'];
    if ($revision === null) {
      return new ErrorResponse(Response::HTTP_BAD_REQUEST,
        'Last revision number must be provided');
    } else if ( ! ctype_digit($revision)) {
      return new ErrorResponse(Response::HTTP_BAD_REQUEST,
        'Malformed revision number');
    }

    $revision = intval($revision, 10);
    $program = $this->programs->getProgram(
      $rq->arguments['program'], $revision);

    if ( ! $user->acl->isSupereditor() &&
        $program->authorId !== $user->id) {
      return new ErrorResponse(Response::HTTP_FORBIDDEN,
        'Cannot modify others\' program');
    }

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
