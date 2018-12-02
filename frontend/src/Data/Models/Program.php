<?php declare(strict_types=1);

namespace PN\Weblight\Data\Models;

use PN\Weblight\Data\BaseModel;

class Program extends BaseModel implements \JsonSerializable
{
  /** @var int */
  public $id, $revision, $authorId;

  /** @var string */
  public $ref, $content;

  /** @return array */
  public function jsonSerialize()
  {
    $json = [ 'id' => $this->ref, 'revision' => $this->revision ];
    if (isset($this->content)) {
      $json['content'] = $this->content;
    }

    return $json;
  }
}
