<?php

namespace Colorium\Web;

use Colorium\Http\Error\HttpException;
use Psr\Log;

abstract class Kernel extends \stdClass
{

    /** @var array */
    public $events = [];

    /** @var array */
    public $errors = [];

    /** @var Log\LoggerInterface */
    public $logger;


    /**
     * Init kernel with logger
     *
     * @param Log\LoggerInterface $logger
     */
    public function __construct(Log\LoggerInterface $logger = null)
    {
        $this->logger = $logger ?: new Log\NullLogger;
        $this->setup();
    }


    /**
     * Setup app
     */
    protected function setup() {}


    /**
     * Run handler
     *
     * @param Context $context
     * @return Context
     *
     * @throws \Exception
     * @throws HttpException
     */
    public function run(Context $context = null)
    {
        $start = microtime(true);

        try {
            try {
                $this->logger->debug('kernel: start');
                if(!$context) {
                    $context = $this->context();
                }
                $this->before($context);
                $context->logger = $this->logger;
                return $this->proceed($context);
            }
            catch(HttpException $event) {
                return $this->event($event, $context);
            }
        }
        catch(\Exception $error) {
            return $this->error($error, $context);
        }
        finally {
            $this->after($context);
            $this->logger->debug('kernel: end (' . number_format(microtime(true) - $start, 4) . 's)');
            $this->logger->debug('---');
        }
    }


    /**
     * Generate Context instance
     *
     * @return Context
     */
    abstract public function context();


    /**
     * Before handler
     *
     * @param Context $context
     */
    protected function before(Context $context) {}


    /**
     * Handle context
     *
     * @param Context $context
     * @return Context
     */
    abstract public function proceed(Context $context);


    /**
     * Handle http event
     *
     * @param HttpException $event
     * @param Context $context
     * @return Context
     *
     * @throws HttpException
     */
    protected function event(HttpException $event, Context $context = null)
    {
        $code = $event->getCode();
        if(isset($this->events[$code])) {
            $this->logger->debug('kernel.event: http ' . $code . ' event raised, has callback');
            return $context->forward($this->events[$code], $event);
        }

        $this->logger->debug('kernel.event: http ' . $code . ' event raised, no callback');
        throw $event;
    }


    /**
     * Handle exception
     *
     * @param \Exception $error
     * @param Context $context
     * @return Context
     *
     * @throws \Exception
     */
    protected function error(\Exception $error, Context $context = null)
    {
        $exception = get_class($error);
        $this->logger->error($error);
        foreach($this->errors as $class => $callback) {
            if(is_string($class) and $exception instanceof $class) {
                $this->logger->debug('kernel.error: ' . $exception . ' raised, has callback');
                return $context->forward($callback, $error);
            }
        }

        $this->logger->debug('kernel.error: ' . $exception . ' raised, no callback');
        throw $error;
    }


    /**
     * After handler
     *
     * @param Context $context
     */
    protected function after(Context $context = null) {}

}