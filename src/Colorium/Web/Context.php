<?php

namespace Colorium\Web;

use Colorium\Http;
use Colorium\Routing;
use Colorium\Runtime;
use Psr\Log;

class Context extends \stdClass
{

    /** @var Http\Request */
    public $request;

    /** @var Routing\Route */
    public $route;

    /** @var array */
    public $params = [];

    /** @var Logic */
    public $logic;

    /** @var object */
    public $user;

    /** @var Http\Response */
    public $response;

    /** @var Log\LoggerInterface */
    public $logger;

    /** @var callable */
    public $forwarder;


    /**
     * Create new context
     *
     * @param Http\Request $request
     * @param Http\Response $response
     */
    public function __construct(Http\Request $request, Http\Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }


    /**
     * Uri helper
     *
     * @param ...$parts
     * @return string
     */
    public function url(...$parts)
    {
        $uri = implode('/', $parts);
        return (string)$this->request->uri->make($uri);
    }


    /**
     * Get post value
     *
     * @param array $keys
     * @return string
     */
    public function post(...$keys)
    {
        if(!$keys) {
            return $this->request->values;
        }
        elseif(count($keys) === 1) {
            return $this->request->value($keys[0]);
        }

        $values = [];
        foreach($keys as $key) {
            $values[] = $this->request->value($key);
        }
        return $values;
    }


    /**
     * Forward to logic
     *
     * @param string $logic name
     * @param $params
     * @return Context
     */
    public function forward($logic, ...$params)
    {
        $context = clone $this;
        $context->params = $params;
        return call_user_func($this->forwarder, $context, $logic);
    }


    /**
     * Terminate context
     *
     * @return string
     */
    public function end()
    {
        return $this->response->send();
    }


    /**
     * Clone context
     */
    public function __clone()
    {
        $this->request = clone $this->request;
        $this->response = clone $this->response;

        if($this->route) {
            $this->route = clone $this->route;
        }

        if($this->logic) {
            $this->logic = clone $this->logic;
        }

        if(is_object($this->user)) {
            $this->user = clone $this->user;
        }
    }

}