<?php declare(strict_types=1);

namespace PN\Weblight\Controllers;

use PN\Weblight\Core\{AppContext, BaseController};
use PN\Weblight\HTTP\{Request, Response};
use PN\Weblight\Views\Environment;

class IndexController extends BaseController
{
  public function frontpage(AppContext $ctx, Request $rq)
  {
    $env = $ctx->get(Environment::class);
    return $env->renderResponse('index.html');
  }
}
