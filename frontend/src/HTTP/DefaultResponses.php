<?php declare(strict_types=1);

namespace PN\Weblight\HTTP;

const MESSAGE_NOT_FOUND = <<<'HTML'
<!DOCTYPE html>
<article>Sorry, not found.</article>

HTML;

const MESSAGE_METHOD_NOT_ALLOWED = <<<'HTML'
<!DOCTYPE html>
<article>Sorry, this error occured: <em>Method Not Allowed.</em></article>

HTML;

const MESSAGE_GENERIC_ERROR = <<<'HTML'
<!DOCTYPE html>
<article>Sorry, something went wrong.</article>

HTML;

class DefaultResponses
{
  public static function notFound()
  {
    return new Response(Response::HTTP_NOT_FOUND, [
      'Content-Type' => 'text/html; charset=UTF-8',
    ], MESSAGE_NOT_FOUND);
  }

  public static function methodNotAllowed()
  {
    return new Response(Response::HTTP_METHOD_NOT_ALLOWED, [
      'Content-Type' => 'text/html; charset=UTF-8',
    ], MESSAGE_METHOD_NOT_ALLOWED);
  }

  public static function internalServerError(\Throwable $e)
  {
    return new Response(Response::HTTP_INTERNAL_SERVER_ERROR, [
      'Content-Type' => 'text/html; charset=UTF-8',
    ], MESSAGE_GENERIC_ERROR);
  }
}
