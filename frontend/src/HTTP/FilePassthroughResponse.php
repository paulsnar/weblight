<?php declare(strict_types=1);

namespace PN\Weblight\HTTP;

use function PN\Weblight\str_starts_with;

class FilePassthroughResponse extends Response
{
  const MIMETYPES = [
    '.css' => 'text/css',
  ];

  public static function detectMimetype(string $filepath)
  {
    $dotpos = strrpos($filepath, '.');
    if ($dotpos === false) {
      return 'application/octet-stream';
    }

    $ext = substr($filepath, $dotpos);
    if ( ! array_key_exists($ext, static::MIMETYPES)) {
      return 'application/octet-stream';
    }

    $mimetype = static::MIMETYPES[$ext];
    if (str_starts_with($mimetype, 'text/')) {
      $mimetype .= '; charset=UTF-8';
    }

    return $mimetype;
  }

  public $filepath;

  public function __construct(string $filepath, ?string $mimetype = null,
    int $status = Response::HTTP_OK, array $extraHeaders = [ ])
  {
    if ($mimetype === null) {
      $mimetype = static::detectMimetype($filepath);
    }

    parent::__construct(
        $status, [ 'Content-Type' => $mimetype ] + $extraHeaders, null);

    $this->body = fopen($filepath, 'r');
  }

  public function __destruct()
  {
    if ($this->body !== null) {
      fclose($this->body);
      $this->body = null;
    }
  }
}

