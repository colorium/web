<?php

namespace Colorium\Web;

use Colorium\Http;
use Colorium\Routing;
use Colorium\Runtime;

class Context extends \stdClass
{

    /** @var Http\Request */
    public $request;

    /** @var Routing\Route */
    public $route;

    /** @var Logic */
    public $logic;

    /** @var object */
    public $user;

    /** @var Http\Response */
    public $response;

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
     * @return Context
     */
    public function forward($logic)
    {
         return call_user_func($this->forwarder, clone $this, $logic);
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
        $this->route = clone $this->route;
        $this->logic = clone $this->logic;
        $this->user = clone $this->user;
        $this->response = clone $this->response;
    }

}