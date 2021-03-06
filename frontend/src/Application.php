<?php declare(strict_types=1);

namespace PN\Weblight;

use PN\Weblight\Core\{AppContext, Configuration, ContextfulRequest,
  DependencyContainer, Router};
use PN\Weblight\Core\Routing\{Route, ControllerHandler, StaticServeHandler};
use PN\Weblight\Curl\{Request as CurlRequest, Session as CurlSession};
use PN\Weblight\Debugging\ErrorResponse as DebugErrorResponse;
use PN\Weblight\HTTP\{DefaultResponses, HTTPSerializable, Request};
use PN\Weblight\Controllers\{AuthController, IndexController, ProgramController};
use PN\Weblight\Controllers\API\{ProgramController as APIProgramController,
  StrandController as APIStrandController};
use const PN\Weblight\ROOT_PUBLIC;
use function PN\Weblight\path_join;
use PN\Weblight\Logging\LogRouter;

class Application
{
  /** @var Configuration */
  public $config;

  /** @var DependencyContainer */
  public $dc;

  /** @var Router */
  protected $routing;

  public function __construct()
  {
    $this->dc = new DependencyContainer();
    $this->config = $this->dc->get(Configuration::class);

    $this->routing = new Router($this->config, [
      new Route('GET', '/',
        new ControllerHandler(IndexController::class, 'frontpage')),

      new Route(['GET', 'POST'], '/auth/login',
        new ControllerHandler(AuthController::class, 'login')),
      new Route(['GET', 'POST'], '/auth/logout',
        new ControllerHandler(AuthController::class, 'logout')),
      new Route('GET', '/auth/change-password',
        new ControllerHandler(AuthController::class, 'showPasswordChangeScreen')),
      new Route('POST', '/auth/change-password',
        new ControllerHandler(AuthController::class, 'changePassword')),

      new Route('GET', '/programs',
        new ControllerHandler(ProgramController::class, 'listPrograms')),
      new Route('GET', '/programs/new',
        new ControllerHandler(ProgramController::class, 'submitProgram')),
      new Route('GET', '/programs/{program:[a-z0-9]{8}}',
        new ControllerHandler(ProgramController::class, 'showProgram')),
      new Route('GET', '/programs/{program:[a-z0-9]{8}}/edit',
        new ControllerHandler(ProgramController::class, 'editProgram')),

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
      new Route('POST', '/api/1/strand/redeploy',
        new ControllerHandler(APIStrandController::class, 'redeployPreviousProgram')),
      new Route('POST', '/api/1/strand/poweroff',
        new ControllerHandler(APIStrandController::class, 'powerOff')),

      new Route('GET', '/static/{file:.+}',
        new StaticServeHandler(path_join(ROOT_PUBLIC, 'static'))),
    ]);

    set_error_handler(function ($severity, $message, $file, $line) {
      throw new \ErrorException($message, 0, $severity, $file, $line);
    });
  }

  public function dispatch()
  {
    /** @var Response|null */
    $response = null;

    try {
      /** @var ContextfulRequest $request */
      $request = ContextfulRequest::fromGlobals($this->dc);
      $request->ctx = new AppContext($this->dc);
      $handler = $this->routing->dispatch($request);
      $response = $handler->handle($request);
    } catch (HTTPSerializable $e) {
      $response = $e->httpSerialize($request);
    } catch (\Throwable $e) {
      if ($this->config->values['debug'] ?? false) {
        $response = new DebugErrorResponse($e);
      } else {
        /** @var LogRouter $log */
        $log = $this->dc->get(LogRouter::class);
        $log->dispatch([ 'exception' => $e ]);
        $response = DefaultResponses::internalServerError($e);
      }
    } finally {
      if ($response !== null) {
        $response->send();
      }
    }
  }
}
