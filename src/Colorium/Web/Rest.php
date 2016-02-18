<?php

namespace Colorium\Web;

use Colorium\Http\Error\AccessDeniedException;
use Colorium\Http\Error\NotFoundException;
use Colorium\Http\Error\NotImplementedException;
use Colorium\Http\Request;
use Colorium\Http\Response;
use Colorium\Routing\Contract\RouterInterface;
use Colorium\Routing\Router;
use Colorium\Runtime\Invokable;
use Colorium\Runtime\Resolver;
use Colorium\Stateful\Auth;

class Rest extends Kernel
{

    /** @var Logic[] */
    protected $logics;

    /** @var RouterInterface */
    protected $router;


    /**
     * Build with logic list
     *
     * @param array $logics
     */
    public function __construct(array $logics)
    {
        $this->router = new Router;

        foreach($logics as $name => $specs) {
            $this->logics[$name] = is_callable($specs)
                ? Logic::resolve($name, $specs)
                : new Logic($name, $specs);
            if($this->logics[$name]->http) {
                $this->router->add($this->logics[$name]->http, $this->logics[$name]);
            }
        }

        parent::__construct();
    }


    /**
     * Get logic by name
     *
     * @param string $name
     * @return Logic
     */
    public function logic($name)
    {
        if(!isset($this->logics[$name])) {
            throw new \InvalidArgumentException('Unknown logic [' . $name . ']');
        }

        return $this->logics[$name];
    }


    /**
     * Handle context
     *
     * @param Context $context
     * @param string $logic
     * @return Context
     *
     * @throws AccessDeniedException
     * @throws NotFoundException
     * @throws NotImplementedException
     */
    public function proceed(Context $context = null, $logic = null)
    {
        // generate context
        $context = $context ?: $this->context();
        $context->forwarder = $context->forwarder ?: [$this, __METHOD__];

        // forward to logic
        if($logic) {
            $context->logic = $this->logic($logic);
        }

        // processes
        $context = $this->route($context);
        $context = $this->guard($context);
        $context = $this->resolve($context);
        $context = $this->execute($context);
        $context = $this->render($context);

        return $context;
    }


    /**
     * Generate context
     *
     * @return Context
     */
    protected function context()
    {
        $this->logger->debug('kernel.context: generate Context instance');

        $request = Request::globals();
        $response = new Response;
        return new Context($request, $response);
    }


    /**
     * Find route from context
     *
     * @param Context $context
     * @return Context
     *
     * @throws NotFoundException
     */
    protected function route(Context $context)
    {
        $this->logger->debug('kernel.route: find route from http query');

        // logic already specified
        if($context->logic) {
            $this->logger->debug('kernel.route: logic [' . $context->logic->name . '] already provided, skip routing');
            return $context;
        }

        // find route
        $query = $context->request->method . ' ' . $context->request->uri->path;
        $route = $this->router->find($query);

        // 404
        if(!$route) {
            throw new NotFoundException('No route found for query ' . $query);
        }

        // update context
        $context->route = $route;
        $context->logic = $route->resource;

        $this->logger->debug('kernel.route: logic [' . $context->logic->name . '] found for query ' . $query);

        return $context;
    }


    /**
     * Check access
     *
     * @param Context $context
     * @return Context
     *
     * @throws AccessDeniedException
     */
    protected function guard(Context $context)
    {
        $this->logger->debug('kernel.guard: check user rank');

        // 401
        if($context->logic->access and $context->logic->access > Auth::rank()) {
            throw new AccessDeniedException('Access denied (logic: ' . $context->logic->access . ', user: ' . $context->logic->access . ')');
        }

        // set user
        if(Auth::valid()) {
            $context->user = Auth::user();
        }

        $this->logger->debug('kernel.guard: access granted (logic: ' . $context->logic->access . ', user: ' . $context->logic->access . ')');

        return $context;
    }


    /**
     * Resolve logic method
     *
     * @param Context $context
     * @return Context
     *
     * @throws NotImplementedException
     */
    protected function resolve(Context $context)
    {
        $this->logger->debug('kernel.resolve: logic method resolving');

        // resolve invokable
        if(!$context->logic->method instanceof \Closure and !$context->logic->method instanceof Invokable) {
            $invokable = Resolver::of($context->logic->method);
            if(!$invokable) {
                throw new NotImplementedException('Invalid method for logic [' . $context->logic->name . ']');
            }

            $context->logic->method = $invokable;
            $this->logger->debug('kernel.resolve: logic [' . $context->logic->name . '] method resolved');
        }

        return $context;
    }


    /**
     * Execute logic
     *
     * @param Context $context
     * @return Context
     */
    protected function execute(Context $context)
    {
        $this->logger->debug('kernel.execute: execute logic');

        // prepare params
        $params = $context->route ? $context->route->params : [];
        $params[] = $context;

        // execute logic method
        $result = call_user_func_array($context->logic->method, $params);
        $this->logger->debug('kernel.execute: logic [' . $context->logic->name . '] executed');

        // user response
        if($result instanceof Response) {
            $context->response = $result;
            $this->logger->debug('kernel.execute: response provided as result of execution');
        }
        // raw response
        else {
            $context->response->content = $result;
            $this->logger->debug('kernel.execute: raw content provided as result of execution');
        }

        return $context;
    }


    /**
     * Render response
     *
     * @param Context $context
     * @return Context
     */
    protected function render(Context $context)
    {
        $this->logger->debug('kernel.render: render Response');

        // resolve output format
        if($context->response->raw) {

            // json
            if($context->logic->render = 'json') {
                $context->response = new Response\Json($context->response->content, $context->response->code, $context->response->headers);
                $this->logger->debug('kernel.render: json response generated');
            }

            $context->reponse->raw = false;
        }

        return $context;
    }

}