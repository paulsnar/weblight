<?php declare(strict_types=1);

namespace PN\Weblight\Services;

use PN\Weblight\Curl\{Request, Session};
use PN\Weblight\Events\EventSubmissionError;
use PN\Weblight\HTTP\Response;

class StrandEventPusherService
{
  protected const EVENT_SERVER_ADDRESS = 'http://127.0.14.1:8000';

  protected static function encodeJSON(array $obj)
  {
    return json_encode($obj, \JSON_UNESCAPED_SLASHES);
  }

  protected $ch;

  public function __construct(Session $ch)
  {
    $this->ch = $ch;
  }

  public function sendEvent(string $event, ?string $data = null)
  {
    $ev = [ 'event' => $event ];
    if ($data !== null) {
      $ev['data'] = $data;
    }

    $rq = Request::post(static::EVENT_SERVER_ADDRESS . '/submit',
      static::encodeJSON($ev));

    $resp = $this->ch->perform($rq);
    if ($resp->status !== Response::HTTP_CREATED) {
      throw new EventSubmissionError($resp->body);
    }
  }

  public function isConnected()
  {
    $rq = Request::get(static::EVENT_SERVER_ADDRESS . '/connected');
    $resp = $this->ch->perform($rq);
    if ($resp->status !== Response::HTTP_OK) {
      throw new \Exception("Backend is not working properly: {$resp->body}");
    }

    return json_decode($resp->body, true);
  }
}
