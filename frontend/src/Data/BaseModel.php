<?php declare(strict_types=1);

namespace PN\Weblight\Data;

use function PN\Weblight\snake_case_to_camel_case;

class BaseModel
{
  public static function fromDatabaseRow(array $row)
  {
    $instance = new static();
    foreach ($row as $attr => $value) {
      if (ctype_digit($value)) {
        $value = intval($value, 10);
      }
      $instance->{snake_case_to_camel_case($attr)} = $value;
    }
    return $instance;
  }
}
