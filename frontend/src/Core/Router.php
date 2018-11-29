<?php declare(strict_types=1);

namespace PN\Weblight\Core;

use FastRoute\{Dispatcher as FRDispatcher, RouteCollector};
use function FastRoute\simpleDispatcher;

use PN\Weblight\Core\Routing\{ControllerHandler, StaticServeHandler};
use PN\Weblight\HTTP\{Request, DefaultResponses};
use function PN\Weblight\str_starts_with;

class Router
{
  protected $config, $r;

  public function __construct(Configuration $config, array $routes)
  {
    $this->config = $config->values['routing'] ?? [ 'prefix' => '' ];

    $this->r = simpleDispatcher(function (RouteCollector $r) use ($routes) {
      foreach ($routes as $route) {
        $r->addRoute($route->method, $route->path, $route->handler);
      }
    });
  }

  public function dispatch(Request $rq)
  {
    if (str_starts_with($this->config['prefix'], $rq->path)) {
      $rq->path = substr($rq->path, strlen($this->config['prefix']));
    }

    $result = $this->r->dispatch($rq->method, $rq->path);

    $status = $result[0];
    switch ($status) {
      case FRDispatcher::NOT_FOUND:
        return [ null, function (Request $rq) {
          return DefaultResponses::notFound();
        } ];

      case FRDispatcher::METHOD_NOT_ALLOWED:
        return [ null, function (Request $rq) {
          return DefaultResponses::methodNotAllowed();
        } ];

      case FRDispatcher::FOUND:
        break;

      default:
        throw new \Exception("Routing failed: unknown code {$status} from FastRoute");
    }

    $rq->arguments = $result[2];
    $handler = $result[1];

    if ($handler instanceof ControllerHandler) {
      return [ $handler->controller, $handler->method ];
    } else if ($handler instanceof StaticServeHandler) {
      return [ null, [ $handler, 'handle' ] ];
    }

    if ( ! is_callable($handler)) {
      throw new \Exception("Routing failed: unknown handler class installed");
    }

    return [ null, $handler ];
  }
}
