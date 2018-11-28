<?php declare(strict_types=1);

namespace PN\Weblight;

function snake_case_to_camel_case(string $source) {
  $words = explode('_', $source);
  $out = array_shift($words);
  $words = array_map('ucfirst', $words);
  $out .= implode('', $words);
  return $out;
}

function camel_case_to_snake_case(string $source) {
  if (strlen($source) < 1) {
    return '';
  }

  $words = [ ];
  $len = strlen($source);
  $word = $source[0];
  for ($i = 1; $i < $len; $i += 1) {
    if (ctype_upper($source[$i])) {
      $words[] = $word;
      $word = strtolower($source[$i]);
    } else {
      $word .= $source[$i];
    }
  }
  $words[] = $word;
  return implode('_', $words);
}

function str_starts_with(string $needle, string $haystack) {
  return substr($haystack, 0, strlen($needle)) === $needle;
}

function str_ends_with(string $needle, string $haystack) {
  return substr($haystack, 0, -1 * strlen($needle)) === $needle;
}

function array_pick(array $array) {
  return $array[array_rand($array)];
}

function maskpos(string $haystack, string $needles, int $offset = 0) {
  $needles = str_split($needles);
  $min = INF;

  foreach ($needles as $needle) {
    $position = strpos($haystack, $needle, $offset);
    if ($position !== false) {
      if ($position < $min) {
        $min = $position;
      }
    }
  }

  if ($min === INF) {
    return false;
  }

  return $min;
}
