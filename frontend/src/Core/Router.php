<?php declare(strict_types=1);

namespace PN\Weblight\Core;

use FastRoute\{Dispatcher as FRDispatcher, RouteCollector};
use function FastRoute\simpleDispatcher;

use PN\Weblight\Core\Routing\{CallableHandler, HandlerInterface};
use PN\Weblight\HTTP\{Request, DefaultResponses};
use function PN\Weblight\str_starts_with;

class Router
{
  /** @var Configuration */
  protected $config;

  /** @var FRDispatcher */
  protected $r;

  public function __construct(Configuration $config, array $routes)
  {
    $this->config = $config->values['routing'] ?? [ 'prefix' => '' ];

    $this->r = simpleDispatcher(function (RouteCollector $r) use ($routes) {
      foreach ($routes as $route) {
        $r->addRoute($route->method, $route->path, $route->handler);
      }
    });
  }

  /**
   * @throws \Exception
   */
  public function dispatch(Request $rq): HandlerInterface
  {
    if (str_starts_with($this->config['prefix'], $rq->path)) {
      $rq->path = substr($rq->path, strlen($this->config['prefix']));
    }

    $result = $this->r->dispatch($rq->method, $rq->path);

    $status = $result[0];
    switch ($status) {
      case FRDispatcher::NOT_FOUND:
        return new CallableHandler([ DefaultResponses::class, 'notFound' ]);

      case FRDispatcher::METHOD_NOT_ALLOWED:
        return new CallableHandler([ DefaultResponses::class, 'methodNotAllowed' ]);

      case FRDispatcher::FOUND:
        break;

      default:
        throw new \Exception("Routing failed: unknown code {$status} from FastRoute");
    }

    $rq->arguments = $result[2];
    $handler = $result[1];

    if (is_callable($handler)) {
      $handler = new CallableHandler($handler);
    }
    return $handler;
  }
}
