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

    /** @var bool */
    public $catch = true;


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
    protected function setup()
    {
        set_error_handler(function($level, $message, $file = null, $line = null) {
            $fatal = E_ERROR | E_PARSE | E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_COMPILE_WARNING;
            if(($level & $fatal) > 0) {
                throw new \ErrorException($message, $level, $level, $file, $line);
            }
        });
    }


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
                $context = $context ?: $this->context();
                $context = $this->before($context);
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
        }
    }


    /**
     * Generate Context instance
     *
     * @return Context
     */
    abstract public function context();


    /**
     * Setup context before process
     *
     * @param Context $context
     * @return Context
     */
    protected function before(Context $context)
    {
        // set context logger
        $context->logger = $this->logger;

        return $context;
    }


    /**
     * Handle context
     *
     * @param Context $context
     * @return Context
     */
    abstract protected function proceed(Context $context);


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
            $context->error = $event;
            $context->response->code = $event->getCode();
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

        if($this->catch) {
            foreach($this->errors as $class => $callback) {
                if(is_string($class) and $error instanceof $class) {
                    $context->error = $error;
                    $this->logger->debug('kernel.error: ' . $exception . ' raised, has callback');
                    return $context->forward($callback, $error);
                }
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
