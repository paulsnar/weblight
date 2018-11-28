<?php declare(strict_types=1);

namespace PN\Weblight\Controllers\API;

use PN\Weblight\API\NotImplementedException;
use PN\Weblight\HTTP\Request;

class StrandController extends BaseAPIController
{
  public function __construct(CurlSession $ch, ProgramStorageService $ps)
  {
    // TODO
    throw new NotImplementedException();
  }
}
