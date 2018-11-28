<?php declare(strict_types=1);

namespace PN\Weblight\Core;

use FastRoute\{Dispatcher as FRDispatcher, RouteCollector};
use function FastRoute\simpleDispatcher;

use PN\Weblight\Core\Routing\ControllerHandler;
use PN\Weblight\HTTP\{Request, DefaultResponses};

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
    if (strpos($rq->path, $this->config['prefix']) === 0) {
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
    }

    if ( ! is_callable($handler)) {
      throw new \Exception("Routing failed: unknown handler class installed");
    }

    return [ null, $handler ];
  }
}
