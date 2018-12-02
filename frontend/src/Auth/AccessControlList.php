<?php declare(strict_types=1);

namespace PN\Weblight\Auth;

class AccessControlList
{
  const
    IS_PROGRAMMER = 1 << 0,
    IS_CONTROLLER = 1 << 1,
    IS_SUPEREDITOR = 1 << 2;

  /** @var int */
  public $level;

  public function isProgrammer(): bool
  {
    return ($this->level & static::IS_PROGRAMMER) !== 0;
  }

  public function isController(): bool
  {
    return ($this->level & static::IS_CONTROLLER) !== 0;
  }

  public function isSupereditor(): bool
  {
    return ($this->level & static::IS_SUPEREDITOR) !== 0;
  }

  public static function fromRow(array $row): self
  {
    $isProgrammer = ($row['is_programmer'] ?? '0') === '1';
    $isController = ($row['is_controller'] ?? '0') === '1';
    $isSupereditor = ($row['is_supereditor'] ?? '0') === '1';

    $instance = new static();
    if ($isProgrammer) {
      $instance->level |= static::IS_PROGRAMMER;
    }
    if ($isController) {
      $instance->level |= static::IS_CONTROLLER;
    }
    if ($isSupereditor) {
      $instance->level |= static::IS_SUPEREDITOR;
    }
    return $instance;
  }
}
