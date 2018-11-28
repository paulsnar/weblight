<?php declare(strict_types=1);

namespace PN\Weblight;

use PN\Weblight\Core\{AppContext, Configuration, DependencyContainer, Router};
use PN\Weblight\Debugging\ErrorResponse as DebugErrorResponse;
use PN\Weblight\Core\Routing\{Route, ControllerHandler};
use PN\Weblight\Curl\{Request as CurlRequest, Session as CurlSession};

use PN\Weblight\HTTP\{DefaultResponses, HTTPSerializable, Request};
use PN\Weblight\Controllers\IndexController;
use PN\Weblight\Controllers\API\{ProgramController as APIProgramController,
  StrandController as APIStrandController};

class Application
{
  public $config, $dc, $ctx;

  public function __construct()
  {
    $this->dc = new DependencyContainer();
    $this->config = $this->dc->get(Configuration::class);

    $this->ctx = new AppContext($this->dc);

    $this->routing = new Router($this->config, [
      new Route('GET', '/', new ControllerHandler(IndexController::class, 'frontpage')),

      new Route('GET', '/api/1/programs',
        new ControllerHandler(APIProgramController::class, 'getProgramList')),
      new Route('POST', '/api/1/programs',
        new ControllerHandler(APIProgramController::class, 'createProgram')),
      new Route('GET', '/api/1/programs/{program:[a-z0-9]{8}}',
        new ControllerHandler(APIProgramController::class, 'getProgram')),
      new Route('PUT', '/api/1/programs/{program:[a-z0-9]{8}}',
        new ControllerHandler(APIProgramController::class, 'updateProgram')),
      new Route('DELETE', '/api/1/programs/{program:[a-z0-9]{8}}',
        new ControllerHandler(APIProgramController::class, 'deleteProgram')),

      new Route('POST', '/api/1/strand/deploy',
        new ControllerHandler(APIStrandController::class, 'deployProgram')),
    ]);

    set_error_handler(function ($severity, $message, $file, $line) {
      throw new \ErrorException($message, 0, $severity, $file, $line);
    });
  }

  public function dispatch()
  {
    $response = null;

    try {
      $request = Request::fromGlobals();
      $invokable = $this->routing->dispatch($request);
      if (is_array($invokable)) {
        $controller = $this->dc->get($invokable[0]);
        $response = $controller->invoke($this->ctx, $invokable[1], $request);
      } else {
        $response = $invokable($request);
      }
    } catch (HTTPSerializable $e) {
      $response = $e->httpSerialize($request);
    } catch (\Throwable $e) {
      if ($this->config->values['debug'] ?? false) {
        $response = new DebugErrorResponse($e);
      } else {
        $response = DefaultResponses::internalServerError($e);
      }
    } finally {
      if ($response !== null) {
        $response->send();
      }
    }
  }
}
