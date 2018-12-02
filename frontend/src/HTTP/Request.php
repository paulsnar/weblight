<?php declare(strict_types=1);

namespace PN\Weblight\HTTP;

use PN\Weblight\Utilities\ImmutableBag;

class Request
{
  /** @var string */
  public $method, $path, $body;
  /** @var Bag */
  public $headers, $query, $form, $files, $cookies;
  /** @var array */
  public $arguments = [ ], $properties = [ ];
  /** @var Authentication|null */
  public $authentication;
  /** @var Session */
  public $session;

  public static function fromGlobals()
  {
    $rq = new static();

    $rq->method = $_SERVER['REQUEST_METHOD'];
    $rq->headers = HeaderBag::fromGlobals();

    $path = $_SERVER['REQUEST_URI'];
    $queryStringStart = strpos($path, '?');
    if ($queryStringStart !== false) {
      $queryString = substr($path, $queryStringStart + 1);
      parse_str($queryString, $query);
      $path = substr($path, 0, $queryStringStart);
    } else {
      $query = $_GET;
    }
    $rq->path = $path;
    $rq->query = new ImmutableBag($query);

    if ($rq->method !== 'HEAD' &&
        $rq->method !== 'GET') {
      $rq->body = file_get_contents('php://input');
    }

    $rq->form = new ImmutableBag($_POST);
    $rq->files = new ImmutableBag($_FILES);
    $rq->cookies = new ImmutableBag($_COOKIE);

    $rq->session = new Session();

    if (array_key_exists('PHP_AUTH_USER', $_SERVER)) {
      $rq->authentication = Authentication::fromGlobals();
    }

    return $rq;
  }
}
