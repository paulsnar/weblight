<?php declare(strict_types=1);

namespace PN\Weblight\HTTP;

const MESSAGE_NOT_FOUND = <<<'HTML'
<!DOCTYPE html>
<article>Atvainojiet, šī lapa netika atrasta.</article>

HTML;

const MESSAGE_METHOD_NOT_ALLOWED = <<<'HTML'
<!DOCTYPE html>
<article>Atvainojiet, notika šāda kļūda: <em>Method Not Allowed.</em></article>

HTML;

const MESSAGE_GENERIC_ERROR = <<<'HTML'
<!DOCTYPE html>
<article>Atvainojiet, kaut kas nogāja greizi.</article>

HTML;

const MESSAGE_FORBIDDEN = <<<'HTML'
<!DOCTYPE html>
<article>Atvainojiet, bet jums nav atļauts tā rīkoties.</article>

HTML;

class DefaultResponses
{
  public static function notFound(): Response
  {
    return new Response(Response::HTTP_NOT_FOUND, [
      'Content-Type' => 'text/html; charset=UTF-8',
    ], MESSAGE_NOT_FOUND);
  }

  public static function forbidden(): Response
  {
    return new Response(Response::HTTP_FORBIDDEN, [
      'Content-Type' => 'text/html; charset=UTF-8',
    ], MESSAGE_FORBIDDEN);
  }

  public static function methodNotAllowed(): Response
  {
    return new Response(Response::HTTP_METHOD_NOT_ALLOWED, [
      'Content-Type' => 'text/html; charset=UTF-8',
    ], MESSAGE_METHOD_NOT_ALLOWED);
  }

  public static function internalServerError(\Throwable $e): Response
  {
    return new Response(Response::HTTP_INTERNAL_SERVER_ERROR, [
      'Content-Type' => 'text/html; charset=UTF-8',
    ], MESSAGE_GENERIC_ERROR);
  }
}
