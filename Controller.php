<?php
/**
 * Controller class  - Controller.php file
 *
 * @author     Dmitriy Tyurin <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2014 Dmitriy Tyurin
 */

namespace Fobia\Base;

/**
 * Controller class
 *
 * @package   Fobia.Base
 */
class Controller
{
    /**
     * @var \Fobia\Base\Application
     */
    public $app;
    public $params   = array();

    public function __construct(Application $app, $params = array())
    {
        $this->app      = $app;
        $this->params   = $params;
    }

    public function section($section = null)
    {
        if ($section === null || !method_exists($this, $section) ) {
            $this->app->notFound();
        }
        
        $args = func_get_args();
        array_shift($args);

        dispatchMethod($this, $section, $args);
    }

    public function index()
    {
        $this->app->notFound();
    }

    public function errorAction()
    {
        $this->app->error();
    }
}