<?php declare(strict_types=1);

namespace PN\Weblight\API;

use PN\Weblight\Core\{BaseController, ContextfulRequest};
use PN\Weblight\Errors\NotFoundException;
use PN\Weblight\Logging\LogRouter;
use PN\Weblight\HTTP\{Request, Response};

class BaseAPIController extends BaseController
{
  public function invoke(string $method, Request $rq): Response
  {
    try {
      return parent::invoke($method, $rq);
    } catch (NotFoundException $e) {
      return new ErrorResponse(Response::HTTP_NOT_FOUND, 'Not found');
    } catch (\Throwable $e) {
      if ($rq instanceof ContextfulRequest) {
        $rq->ctx->get(LogRouter::class)->dispatch([ 'exception' => $e ]);
      }
      return new ErrorResponse(Response::HTTP_INTERNAL_SERVER_ERROR, 'Internal error');
    }
  }
}
