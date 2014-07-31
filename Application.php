<?php
/**
 * Application class  - Application.php file
 *
 * @author     Tyurin D. <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2012 AC Software
 */

namespace Fobia\Base;

use Fobia\Base\Utils;
use Fobia\Debug\Log;

/**
 * Application class
 *
 * @property \Fobia\DataBase\Handler\MySQL $db database
 * @property \Slim\Session $session current session
 * @property \Fobia\Auth\Authentication $auth
 *
 */
class Application extends \Slim\App
{

    const MODE_DEVELOPMENT = 'development';
    const MODE_PRODUCTION  = 'production';
    const MODE_TESTING     = 'testing';
    // protected static $instance = null;

    protected static $instance = array();

    /**
     * @return \Fobia\Base\Application
     */
    public static function getInstance($name = null)
    {
        if ($name === null) {
            $name = 0;
        }

        $app = self::$instance[$name];
        if ( ! ($app instanceof Application)) {
            throw new \RuntimeException("Error Processing Request", 1);
        }
        return $app;
    }

    /**
     * Set Instance Application
     *
     * @param \Fobia\Base\Application $app
     * @param string $name
     */
    public static function setInstance(Application $app, $name = null)
    {
        if ($name === null) {
            $name = 0;
        }
        self::$instance[$name] = $app;
    }

    //-------------------------------------------------------------------------
    //-------------------------------------------------------------------------

    /**
     * @internal
     */
    public function __construct($userSettings = null)
    {
        $configDir = '.';

        // Пользовательские настройки
        // --------------------------
        if ( ! is_array($userSettings)) {
            $file = $userSettings;
            $userSettings = array();
            if (file_exists($file)) {
                $configDir = dirname($file);

                $userSettings = Utils::loadConfig($file);
                Log::debug("Configuration load: " . realpath($file) );
                unset($file);
            }
        }
        if ($p = $userSettings['templates.path']) {
            if (substr($p, 0, 1) !== '/') {
                $userSettings['templates.path'] = SYSPATH . '/' . $p;
            }
        }

        // if (is_array($userSettings['import'])) {
        //     $import = $userSettings['import'];
        //     foreach ($import as $file) {
        //         $file     = $configDir . '/' . $file;
        //         $settings = Utils::loadConfig($file);
        //         if ($settings) {
        //             $defaultSettings = array_merge($defaultSettings, $settings);
        //             Log::debug("Configuration import",
        //                               array(realpath($file)));
        //         }
        //     }
        // }

        parent::__construct((array) $userSettings);
        $app = & $this;

        // Если Internet Explorer, то шлем на хуй
        /*
          if (preg_match('/(rv:11.0|MSIE)/i', $_SERVER['HTTP_USER_AGENT'])) {
          $app->log->warning('Bad Browser ', array('HTTP_USER_AGENT'=>$_SERVER['HTTP_USER_AGENT']));
          $app->redirect( "http://fobia.github.io/docs/badbrowser.html" );
          exit();
          }
         */

        // // Автоматическая загрузка секций конфигурации
        // ----------------------------------------------
        $autoload = $this['settings']['app.autoload'];
        if ($autoload) {
            $this['settings']['app']             = new \Pimple();
            $this['settings']['app']['autoload'] = $autoload;
            if (is_array($autoload)) {
                foreach (@$autoload as $cfg => $file) {
                    $this['settings']['app'][$cfg] = function($c) use($cfg, $file, $configDir) {
                        Log::debug(">> autoload config", array($cfg, $file));
                        if ( ! file_exists($configDir . "/$file")) {
                            trigger_error("Нет автозагрузочной секции конфигурации '$cfg'" . "/$file", E_USER_ERROR);
                            return;
                        }
                        return Utils::loadConfig($file);
                    };
                }
            }
        }
        unset($autoload, $cfg, $file);

        // Session
        //  session.gc_maxlifetime = 1440
        //  ;setting session.gc_maxlifetime to 1440 (1440 seconds = 24 minutes):
        // ------------------
        $this['session'] = function($c) {
            $sid = null;
            if (@$_COOKIE['SID']) {
                $sid = $_COOKIE['SID'];
            }
            if ($sid) {
                session_id($sid);
            }
            $session = new \Slim\Session($c['settings']['session.handler']);
            $session->start();
            if ($c['settings']['session.encrypt'] === true) {
                $session->decrypt($c['crypt']);
            }
            if ($sid === null) {
                $sid =  session_id();
                $c->setCookie('SID', $sid, time() + 1440);
                Log::debug('Session save cookie');
            }

            Log::debug('Session start', array($sid));

            return $session;
        };

        // View
        // ------------------
        $this['view'] = function($c) {
            $view = $c['settings']['view'];
            if ($view instanceof \Slim\Interfaces\ViewInterface !== false) {
                return $view;
            }

            $view = new \Slim\Views\Smarty($c['settings']['templates.path']);
            $view->parserExtensions       = SRC_DIR . '/src/Slim/Views/SmartyPlugins';
            $view->parserCompileDirectory = CACHE_DIR . '/templates';
            $view->parserCacheDirectory   = CACHE_DIR ;

            return $view;
        };

        // Database
        // ------------------
        $this['db'] = function($c) {
            $db = \Fobia\DataBase\DbFactory::create($c['settings']['database']);
            \ezcDbInstance::set($db);
            return $db;
        };

        // Auth
        // ------------------
        $this['auth'] = function($c) use($app) {
            $auth = new \Fobia\Auth\Authentication($app);
            $auth->authenticate();
            return $auth;
        };

        // API
        // ------------------
        $this['apiHandler'] = function() {
            return new \Api\ApiHandler();
        };
        $this['api'] = $this->protect(function($method, $params = null) use ($app)  {
            $result = $app['apiHandler']->request($method, $params);
            return $result;
        });

        // ------------------
        if ( ! self::$instance[0]) {
            self::setInstance($this);
        }
    }

    /**
     * @internal
     */
    public function __get($name)
    {
        return $this[$name];
    }

    /**
     * Хеширование строки по настройкам приложухи
     *
     * @param string $value   строка для хеширования
     * @return string  хешированая строка
     */
    public function hash($value)
    {
        return  hash_hmac(
                    $this['settings']['crypt.method'],
                    $value,
                    $this['settings']['crypt.key']
        );
    }

    /**
     * Возвращает функцию автозоздания контролера
     *
     * @param string $controller
     * @return callable
     */
    public function createController($controller)
    {
        // controller.prefix
        // controller.suffix
        // controller.action_prefix
        // controller.action_suffix

        list( $class, $method ) = explode(':', $controller);

        // Method name
        if (!$method) {
            $method = 'index';
        }
        $method =  $this['settings']['controller.action_prefix']
            . $method
            . $this['settings']['controller.action_suffix'];

        // Class name
        $class =  $this['settings']['controller.prefix']
            . $class
            . $this['settings']['controller.suffix'];
        $class = str_replace('.', '_', $class);

        // Class arguments
        $classArgs = func_get_args();
        array_shift($classArgs);
        $app = & $this;

        return function() use ( $app, $classArgs, $class, $method ) {
            $methodArgs = func_get_args();
            $classController = new $class( $app, $classArgs );
            Log::debug("Call controller: $class -> $method", $methodArgs);
            return call_user_func_array(array($classController, $method), $methodArgs);
        };
    }

    /**
     * Добавить маршрут без метода HTTP
     *
     * @param string  $path   маршрут
     * @param string|callable $controller карта контроллера или функция
     * @return \Slim\Route
     */
    public function route($path, $controller)
    {
        $args = func_get_args();
        $controller = array_pop($args);
        if (is_callable($controller)) {
            $callable = $controller;
        } else {
            $callable = $this->createController($controller);
        }
        array_push($args, $callable);
        return call_user_func_array(array($this, 'map'), $args);
    }

    /**
     *
     * @return boolean
     */
    public function isCli()
    {
        defined('IS_CLI') or define('IS_CLI',  ! defined('SYSPATH'));
        return IS_CLI;
    }

    /**
     * Базовый url путь
     *
     * @param string  $url
     * @return string
     */
    public function urlForBase($url = '')
    {
        $url = $this->urlFor('base') . $url;
        return preg_replace('|/+|', '/', $url);
    }

    /* ***********************************************
     * OVERRIDE
     * ********************************************** */

    /**
     *
     */
    protected function defaultNotFound()
    {
        $this->status(404);
        $view = new \Slim\View($this->config('templates.path'));
        $view->display('error/404.php');
    }

    protected function defaultError($e)
    {
        $this->status(500);
        $view = new \Slim\View($this->config('templates.path'));
        $view->display('error/500.php');
        echo $e->xdebug_message;
       //parent::defaultError($e);
    }

    protected function dispatchRequest(\Slim\Http\Request $request, \Slim\Http\Response $response)
    {
        Log::debug('App run dispatch request');
        try {
            $this->applyHook('slim.before');
            ob_start();
            $this->applyHook('slim.before.router');
            $dispatched = false;
            $matchedRoutes = $this['router']->getMatchedRoutes($request->getMethod(), $request->getPathInfo(), true);
            foreach ($matchedRoutes as $route) {
                /* @var $route \Slim\Route */
                try {
                    $this->applyHook('slim.before.dispatch');
                    $dispatched = $route->dispatch();
                    $this->applyHook('slim.after.dispatch');
                    if ($dispatched) {
                        Log::debug('Route dispatched: ' . $route->getPattern());
                        break;
                    }
                } catch (\Slim\Exception\Pass $e) {
                    continue;
                }
            }
            if (!$dispatched) {
                $this->notFound();
            }
            $this->applyHook('slim.after.router');
        } catch (\Slim\Exception\Stop $e) {}
        $response->write(ob_get_clean());
        $this->applyHook('slim.after');
    }

}