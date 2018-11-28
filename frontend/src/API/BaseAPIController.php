<?php declare(strict_types=1);

namespace PN\Weblight\API;

use PN\Weblight\Core\{AppContext, BaseController};
use PN\Weblight\Errors\NotFoundException;
use PN\Weblight\HTTP\{Request, Response};

class BaseAPIController extends BaseController
{
  public function invoke(AppContext $ctx, string $method, Request $rq): Response
  {
    try {
      return parent::invoke($ctx, $method, $rq);
    } catch (NotFoundException $e) {
      return new ErrorResponse(Response::HTTP_NOT_FOUND, 'Not found');
    } catch (\Throwable $e) {
      $ctx->log([ 'exception' => $e ]);
      return new ErrorResponse(Response::HTTP_INTERNAL_SERVER_ERROR, 'Internal error');
    }
  }
}
