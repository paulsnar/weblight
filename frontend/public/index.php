<?php declare(strict_types=1);

namespace PN\Weblight;

function path_join(...$parts) {
  return implode(DIRECTORY_SEPARATOR, $parts);
}

define(__NAMESPACE__ . '\\ROOT_PUBLIC', __DIR__);
define(__NAMESPACE__ . '\\ROOT_PRIVATE', dirname(__DIR__));

require path_join(ROOT_PRIVATE, 'vendor', 'autoload.php');
(new Application)->dispatch();
