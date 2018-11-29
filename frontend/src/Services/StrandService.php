<?php declare(strict_types=1);

namespace PN\Weblight\Services;

use PN\Weblight\Curl\{Request as CurlRequest, Session as CurlSession};
use PN\Weblight\HTTP\Response as HTTPResponse;

class StrandService
{
  protected $strandEventPusher, $programs;

  public function __construct(StrandEventPusherService $sep, ProgramStorageService $ps)
  {
    $this->strandEventPusher = $sep;
    $this->programs = $ps;
  }

  public function deployProgram(string $ref, ?int $revision = null)
  {
    if ($revision === null) {
      $revision = $this->programs->getLatestRevision($ref);
    }

    $this->strandEventPusher->sendEvent('launch', "{$ref}-{$revision}");
  }

  public function redeployLastProgram()
  {
    $this->strandEventPusher->sendEvent('launch-last');
  }

  public function powerOff()
  {
    $this->strandEventPusher->sendEvent('off');
  }
}
