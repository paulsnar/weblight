<?php declare(strict_types=1);

namespace PN\Weblight\Errors;

use PN\Weblight\HTTP\{DefaultResponses, HTTPSerializable, Request, Response};

class NotFoundException extends \Exception implements HTTPSerializable
{
  public function httpSerialize(Request $rq): Response
  {
    return DefaultResponses::notFound();
  }
}
