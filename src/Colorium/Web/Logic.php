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
    public $view;

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
        foreach($specs as $key => $value) {
            $this->$key = $value;
        }
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
        unset(
            $annotations['param'],
            $annotations['return'],
            $annotations['throws'],
            $annotations['method']
        );
        $annotations['method'] = $invokable;

        return new Logic($name, $annotations);
    }

}