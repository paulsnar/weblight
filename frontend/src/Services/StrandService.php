<?php declare(strict_types=1);

namespace PN\Weblight\Services;

use PN\Weblight\Curl\{Request as CurlRequest, Session as CurlSession};
use PN\Weblight\HTTP\Response as HTTPResponse;

class StrandService
{
  protected $ch, $programs;

  public function __construct(CurlSession $ch, ProgramStorageService $ps)
  {
    $this->ch = $ch;
    $this->programs = $ps;
  }

  public function deployProgram(string $ref, ?int $revision = null)
  {
    if ($revision === null) {
      $program = $this->programs->getLatestProgram($ref);
    } else {
      $program = $this->programs->getProgram($ref, $revision);
    }

    $rq = CurlRequest::post('http://127.0.14.1:8000/submit', json_encode([
      'event' => 'reprogram',
      'data' => $program->content,
    ], \JSON_UNESCAPED_SLASHES));
    $resp = $this->ch->perform($rq);

    if ($resp->status !== HTTPResponse::HTTP_CREATED) {
      throw new \Exception("Could not submit event: {$resp->body}");
    }
  }

  public function powerOff()
  {
    $rq = CurlRequest::post('http://127.0.14.1:8000/submit', json_encode([
      'event' => 'off',
    ], \JSON_UNESCAPED_SLASHES));
    $resp = $this->ch->perform($rq);

    if ($resp->status !== HTTPResponse::HTTP_CREATED) {
      throw new \Exception("Could not submit event: {$resp->body}");
    }
  }
}
