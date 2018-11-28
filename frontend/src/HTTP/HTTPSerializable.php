<?php declare(strict_types=1);

namespace PN\Weblight\HTTP;

interface HTTPSerializable
{
  public function httpSerialize(Request $rq): Response;
}
