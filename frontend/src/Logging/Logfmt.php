<?php declare(strict_types=1);

namespace PN\Weblight\Logging;

use function PN\Weblight\maskpos;

abstract class Logfmt
{
  public static function encode(array $items): string
  {
    $out = '';
    $arraySentinel = -1;

    foreach ($items as $index => $value) {
      if ( ! is_string($value)) {
        $value = strval($value);
      }

      if (maskpos($value, "\"\\ \n") !== false) {
        $value = str_replace(
          [ '"', "\\" ],
          [ '\\"', "\\\\" ], $value);
        $value = "\"{$value}\"";
      }

      if (is_integer($index) && $index - $arraySentinel === 1) {
        $arraySentinel += 1;
        $out .= " {$v}";
      } else {
        $key = $index;
        if (maskpos($key, "\"\\ \n") !== false) {
          $key = str_replace(
            [ '"', '\\' ],
            [ '\\"', "\\\\" ], $key);
          $key = "\"{$key}\"";
        }
        $out .= " {$key}={$value}";
      }
    }

    return substr($out, 1); // trim preceding space
  }
}
