<?php declare(strict_types=1);

namespace PN\Weblight\Curl;

class Request
{
  /** @var string */
  public $method, $url;

  /** @var string[] */
  public $headers = [
    'User-Agent' => 'pn.weblight-frontend/v0.01 (+https://pn.id.lv)',
  ];

  /** @var string|resource|null */
  public $data;

  public function __construct(string $method, string $url, $data = null)
  {
    $this->method = $method;
    $this->url = $url;
    $this->data = $data;
  }

  /**
   * @param resource $ch
   */
  public function serializeUnto($ch)
  {
    $headers = [ ];
    foreach ($this->headers as $name => $value) {
      $headers[] = "{$name}: {$value}";
    }

    curl_setopt_array($ch, [
      CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
      CURLOPT_CUSTOMREQUEST => $this->method,
      CURLOPT_URL => $this->url,
      CURLOPT_HTTPHEADER => $headers,
    ]);

    if (is_resource($this->data)) {
      curl_setopt($ch, CURLOPT_INFILE, $this->data);
    } else if (is_string($this->data) || is_array($this->data)) {
      curl_setopt($ch, CURLOPT_POSTFIELDS, $this->data);
    }
  }

  public static function get(string $url): self
  {
    return new static('GET', $url, null);
  }

  /**
   * @param string|resource|null $body
   */
  public static function post(string $url, $body = null): self
  {
    return new static('POST', $url, $body);
  }
}
