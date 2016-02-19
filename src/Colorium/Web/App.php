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
    public function __construct(array $logics = [])
    {
        $this->templater = new Templater;

        parent::__construct($logics);
    }


    /**
     * Execute logic
     *
     * @param Context $context
     * @return Context
     */
    protected function execute(Context $context)
    {
        // add tempalter to context
        $context->templater = $this->templater;

        // prepare templater helpers and globals vars
        $this->templater->vars['ctx'] = $context;
        $this->templater->helpers['url'] = [$context, 'url'];
        $this->templater->helpers['call'] = [$context, 'forward'];

        return parent::execute($context);
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
            if($template = $context->logic->html) {

                $vars = $context->response->content;
                if(!is_array($vars)) {
                    $vars = (array)$vars;
                }

                $content = $this->templater->render($template, $vars);
                $context->response->content = $content;
                $context->response->format = 'text/html';
                $this->logger->debug('kernel.process.render: template "' . $template . '" compiled');
            }
            // json
            elseif($context->logic->render = 'json') {
                $context->response = new Response\Json($context->response->content, $context->response->code, $context->response->headers);
                $this->logger->debug('kernel.process.render: json response generated');
            }

            $context->response->raw = false;
        }

        // template response
        // todo

        // redirect response
        // todo

        return $context;
    }

}