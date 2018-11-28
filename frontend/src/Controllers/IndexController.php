<?php declare(strict_types=1);

namespace PN\Weblight\Controllers;

use PN\Weblight\HTTP\{Request, Response};

class IndexController
{
  public function frontpage(Request $rq)
  {
    return new Response(Response::HTTP_OK, [
      'Content-Type' => 'text/plain; charset=UTF-8',
    ], "Hello, world!\n");
  }
}
