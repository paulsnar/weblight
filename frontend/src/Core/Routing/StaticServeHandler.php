<?php declare(strict_types=1);

namespace PN\Weblight\Core\Routing;

use PN\Weblight\Core\AppContext;
use PN\Weblight\Errors\NotFoundException;
use PN\Weblight\HTTP\{DefaultResponses, FilePassthroughResponse, Request, Response};
use function PN\Weblight\{path_normalize, path_join};

class StaticServeHandler implements HandlerInterface
{
  /** @var string */
  protected $base;

  public function __construct(string $base)
  {
    $this->base = $base;
  }

  public function handle(Request $rq): Response
  {
    if (array_key_exists('file', $rq->arguments)) {
      return $this->handleFile($rq);
    }

    throw new NotFoundException();
  }

  protected function handleFile(Request $rq): Response
  {
    $realPath = path_normalize($rq->arguments['file']);
    $fullPath = path_join($this->base, $realPath);

    if (is_file($fullPath)) {
      return new FilePassthroughResponse($fullPath);
    } else if (is_dir($fullPath)) {
      return DefaultResponses::forbidden();
    }

    throw new NotFoundException();
  }
}
