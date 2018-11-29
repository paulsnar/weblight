<?php declare(strict_types=1);

namespace PN\Weblight\Views;

use PN\Weblight\Core\Configuration;
use PN\Weblight\HTTP\Response;
use const PN\Weblight\ROOT_PRIVATE;
use function PN\Weblight\path_join;

use Twig\Loader\Filesystem as FilesystemLoader;
use Twig\Environment as TwigEnvironment;

class Environment
{
  protected $twig;

  public function __construct(Configuration $conf)
  {
    $loader = new FilesystemLoader(path_join(ROOT_PRIVATE, 'views'));
    $this->twig = new TwigEnvironment($loader, [
      'cache' => path_join(ROOT_PRIVATE, 'var', 'cache', 'twig'),
      'auto_reload' => $conf->values['debug'] ?? false,
    ]);
  }

  public function render(string $name, array $context = [ ])
  {
    $template = $this->twig->load($name);
    return $template->render($context);
  }

  public function renderResponse(string $name, array $context = [ ],
    int $status = Response::HTTP_OK, array $headers = [ ])
  {
    $tpl = $this->render($name, $context);
    return new Response($status, $headers, $tpl);
  }
}
