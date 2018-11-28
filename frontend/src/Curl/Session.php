<?php declare(strict_types=1);

namespace PN\Weblight\Curl;

class Session
{
  protected $ch;

  public function __construct()
  {
    $this->ch = curl_init();
  }

  public function __destruct()
  {
    curl_close($this->ch);
  }

  public function perform(Request $rq): Response
  {
    $ch = $this->ch;

    $headers = [ ];

    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,

      CURLOPT_HEADERFUNCTION => function($ch, $hd) use (&$headers) {
        $header = explode(':', $hd, 2);
        if (count($header) < 2) {
          goto done;
        }

        [ $name, $value ] = $header;
        $headers[trim($name)] = trim($value);

      done:
        return strlen($hd);
      },
    ]);
    $rq->serializeUnto($ch);

    $respData = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

    return new Response($status, $headers, $respData);
  }
}
