<?php

declare(strict_types=1);

namespace Lefteris\EverypayTask\Router;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Lefteris\EverypayTask\Infrastructure\Request;
use Lefteris\EverypayTask\Infrastructure\Response;

use function FastRoute\simpleDispatcher;

class Router
{
    private array $routes     = [];
    private array $middleware = [];

    public function addRoute(string $method, string $path, array|callable $handler): void
    {
        $this->routes[] = [$method, $path, $handler];
    }

    public function addMiddleware(callable $middleware): void
    {
        $this->middleware[] = $middleware;
    }

    public function dispatch(Request $request, Response $response): void
    {
        $dispatcher = simpleDispatcher(function (RouteCollector $r) {
            foreach ($this->routes as [$method, $path, $handler]) {
                $r->addRoute($method, $path, $handler);
            }
        });

        $uri = $request->getUri();

        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = rawurldecode($uri);

        $routeInfo = $dispatcher->dispatch($request->getMethod(), $uri);

        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                $response->setStatusCode(404)->setBody('404 Not Found')->send();
                break;

            case Dispatcher::METHOD_NOT_ALLOWED:
                $response->setStatusCode(405)->setBody('405 Method Not Allowed')->send();
                break;

            case Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $vars    = $routeInfo[2];

                $final = function (Request $request, Response $response) use ($handler, $vars): void {
                    if (is_callable($handler)) {
                        $handler($request, $response, $vars);
                    } elseif (is_array($handler) && count($handler) === 2) {
                        [$classOrObject, $method] = $handler;
                        $instance = is_object($classOrObject) ? $classOrObject : new $classOrObject();
                        $instance->$method($request, $response, $vars);
                    }
                };

                $chain = array_reduce(
                    array_reverse($this->middleware),
                    fn(callable $next, callable $mw) => fn($req, $res) => $mw($req, $res, $next),
                    $final,
                );

                $chain($request, $response);
                break;
        }
    }
}
