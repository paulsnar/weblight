<?php declare(strict_types=1);

namespace PN\Weblight\Auth;

class AuthenticationAttemptFailure extends \Exception
{
  /** @var string|null */
  public $publicMessage, $previousUsername;

  public function __construct(
    string $internalMessage,
    ?string $publicMessage = null,
    ?string $previousUsername = null
  ) {
    parent::__construct($internalMessage);
    $this->publicMessage = $publicMessage;
    $this->previousUsername = $previousUsername;
  }
}
