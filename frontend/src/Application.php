<?php declare(strict_types=1);

namespace PN\Weblight;

use PN\Weblight\Core\{Configuration, DependencyContainer, Router};
use PN\Weblight\Debugging\ErrorResponse as DebugErrorResponse;
use PN\Weblight\Core\Routing\{Route, ControllerHandler};
use PN\Weblight\Curl\{Request as CurlRequest, Session as CurlSession};

use PN\Weblight\HTTP\{DefaultResponses, Request};
use PN\Weblight\Controllers\IndexController;

class Application
{
  public $config, $dc;

  public function __construct()
  {
    $this->dc = new DependencyContainer();
    $this->config = $this->dc->get(Configuration::class);

    $this->routing = new Router($this->config, [
      new Route('GET', '/', new ControllerHandler(IndexController::class, 'frontpage')),
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
      [ $context, $method ] = $this->routing->dispatch($request);

      if ($context !== null) {
        $response = $this->dc->invoke($context, $method, $request);
      } else {
        $response = $method($request);
      }
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

  public function handle(CurlSession $ch)
  {
    $rq = CurlRequest::get('http://127.0.14.1:8000/api/1/program');
    $resp = $ch->perform($rq);
    header('Content-Type: text/plain; charset=UTF-8');
    var_dump($resp);
  }
}
