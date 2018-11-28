<?php declare(strict_types=1);

namespace PN\Weblight\Data\Models;

use PN\Weblight\Data\BaseModel;

class Program extends BaseModel implements \JsonSerializable
{
  public $id, $ref, $revision, $content;

  public function jsonSerialize()
  {
    $json = [ 'id' => $this->ref, 'revision' => $this->revision ];
    if (isset($this->content)) {
      $json['content'] = $this->content;
    }
    return $json;
  }
}
