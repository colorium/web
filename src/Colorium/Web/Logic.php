<?php

namespace Colorium\Web;

use Colorium\Runtime\Resolver;

class Logic extends \stdClass
{

    /** @var string */
    public $name;

    /** @var string */
    public $http;

    /** @var int */
    public $access = 0;

    /** @var string */
    public $render = 'text';

    /** @var string */
    public $html;

    /** @var callable */
    public $method;


    /**
     * Config new logic unit
     *
     * @param string $name
     * @param array $specs
     */
    public function __construct($name, array $specs = [])
    {
        $this->name = $name;
        $this->override($specs);
    }


    /**
     * Override data
     *
     * @param array $specs
     * @return $this
     */
    public function override(array $specs)
    {
        unset($specs['param'], $specs['return'], $specs['throws']);
        foreach($specs as $key => $value) {
            $this->$key = $value;
        }

        return $this;
    }


    /**
     * Resolve logic from callable
     *
     * @param string $name
     * @param $callable
     * @return Logic
     */
    public static function resolve($name, $callable)
    {
        $invokable = Resolver::of($callable);
        $annotations = $invokable->annotations();
        $annotations['method'] = $invokable;

        return new Logic($name, $annotations);
    }

}