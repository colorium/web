<?php

namespace Colorium\Web;

use Colorium\Http\Response;
use Colorium\Templating\Contract\TemplaterInterface;
use Colorium\Templating\Templater;

class App extends Rest
{

    /** @var TemplaterInterface */
    public $templater;


    /**
     * Config new app
     *
     * @param array $logics
     */
    public function __construct(array $logics)
    {
        $this->templater = new Templater;

        parent::__construct($logics);
    }


    /**
     * Render response
     *
     * @param Context $context
     * @return Context
     */
    protected function render(Context $context)
    {
        $this->logger->debug('kernel.process.render: render Response');

        // resolve output format
        if($context->response->raw) {

            // template
            if($template = $context->logic->view) {
                $content = $this->templater->render($template, $context->response->content);
                $context->response->content = $content;
                $this->logger->debug('kernel.process.render: template "' . $template . '" compiled');
            }
            // json
            elseif($context->logic->render = 'json') {
                $context->response = new Response\Json($context->response->content, $context->response->code, $context->response->headers);
                $this->logger->debug('kernel.process.render: json response generated');
            }

            $context->reponse->raw = false;
        }

        // template response
        // todo

        // redirect response
        // todo

        return $context;
    }

}