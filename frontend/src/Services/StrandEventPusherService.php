<?php declare(strict_types=1);

namespace PN\Weblight\Services;

use PN\Weblight\Curl\{Request, Session};
use PN\Weblight\Events\EventSubmissionError;
use PN\Weblight\HTTP\Response;

class StrandEventPusherService
{
  protected const EVENT_SERVER_ADDRESS = 'http://weblight:8080';

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
    $url = static::EVENT_SERVER_ADDRESS . '/submit';
    $url .= '?event=' . rawurlencode($event);
    if ($data !== null) {
      $url .= '&data=' . rawurlencode($data);
    }

    $rq = Request::post($url);

    $resp = $this->ch->perform($rq);
    if ($resp->status !== Response::HTTP_NO_CONTENT) {
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
