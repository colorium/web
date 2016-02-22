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
     * Before handler
     *
     * @param Context $context
     * @return Context
     */
    protected function before(Context $context)
    {
        // kernel before handler
        $context = parent::before($context);

        // add templater to context
        $context->templater = $this->templater;

        // prepare templater helpers and globals vars
        $this->templater->vars['ctx'] = $context;
        $this->templater->helpers['url'] = [$context, 'url'];
        $this->templater->helpers['call'] = [$context, 'forward'];

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
        $this->logger->debug('kernel.process.render: render Response');

        // resolve output format
        if($context->response->raw) {

            // template
            if($template = $context->logic->html) {

                // render template
                $vars = $context->response->content;
                $vars = is_array($vars) ? $vars : (array)$vars;
                $content = $this->templater->render($template, $vars);

                // set html response
                $context->response = new Response\Html($content);
                $this->logger->debug('kernel.process.render: template "' . $template . '" compiled');
            }
            // json
            elseif($context->logic->render = 'json') {
                $context->response = new Response\Json($context->response->content, $context->response->code, $context->response->headers);
                $this->logger->debug('kernel.process.render: json response generated');
            }

            // remove auto-generated flag
            $context->response->raw = false;
        }

        // template response
        // todo

        // redirect response
        // todo

        return $context;
    }

}