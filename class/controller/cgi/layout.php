<?php

namespace Controller\CGI {

    class Layout extends \Controller\CGI
    {
        public $view;
        protected static $layout_name = 'layout';

        function __preAction($action, &$params)
        {
            parent::__preAction($action, $params);
            $this->view = V(static::$layout_name);
            $this->view->title = \Gini\Config::get('layout.title');
        }

        function __postAction($action, &$params, $response)
        {
            parent::__postAction($action, $params, $response);
            if (null === $response) $response = new \Gini\CGI\Response\HTML($this->view);
            return $response;
        }

    }

}