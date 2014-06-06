<?php
/**
 * Application class  - Application.php file
 *
 * @author     Tyurin D. <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2012 AC Software
 */

namespace Fobia\Base;

use \Fobia\Base\Utils;

/**
 * Application class
 *
 * @property \ezcDbHandler $db database
 * @property \Slim\Session $session current session
 * @property \Psr\Log\LoggerInterface $log логер
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
     * @param \Fobia\Application $app
     * @param string $name
     */
    public static function setInstance(Application $app, $name = null)
    {
        if ($name === null) {
            $name = 0;
        }
        self::$instance[$name] = $app;
    }

    /**
     * @internal
     */
    public function __construct($userSettings = null)
    {
        if ( ! is_array($userSettings)) {
            $userSettings = (array) $userSettings;
        }

        $dirs = array(__DIR__ . '/../../config', __DIR__ . '/../config');
        if (defined('CONFIG_DIR')) {
            array_unshift($dirs, CONFIG_DIR);
        }
        foreach ($dirs as $dir) {
            $f = $dir . "/config.yml";
            if (file_exists($f)) {
                $defaultSettings = Utils::loadConfigCache($f);
                Log::debug("Configuration read from", array(realpath($f)));
                break;
            }
        }
        if (!is_array($defaultSettings)) {
            $defaultSettings = array();
        }

        if (is_array($userSettings)) {
            $defaultSettings = array_replace($defaultSettings, $userSettings);
        }

        if (is_array($defaultSettings['import'])) {
            foreach ($defaultSettings['import'] as $file) {
                $file     = $configDir . '/' . $file;
                $settings = Utils::loadConfig($file);
                if ($settings) {
                    $defaultSettings = array_merge($defaultSettings, $settings);
                    Log::debug("Configuration import",
                                      array(realpath($file)));
                }
            }
        }

        parent::__construct($defaultSettings);

        // Если Internet Explorer, то шлем на хуй
        /*
          if (preg_match('/(rv:11.0|MSIE)/i', $_SERVER['HTTP_USER_AGENT'])) {
          $app->log->warning('Bad Browser ', array('HTTP_USER_AGENT'=>$_SERVER['HTTP_USER_AGENT']));
          $app->redirect( "http://fobia.github.io/docs/badbrowser.html" );
          exit();
          }
         */

        // // Автоматическая загрузка секций конфигурации
        // -------------------------------------------
        $autoload = $this['settings']['app.autoload'];
        if ($autoload) {
            $this['settings']['app']             = new \Pimple();
            $this['settings']['app']['autoload'] = $autoload;
            if (is_array($autoload)) {
                foreach (@$autoload as $cfg => $file) {
                    $this['settings']['app'][$cfg] = function($c) use($cfg, $file, $configDir) {
                        Log::debug(">> autoload config",
                                          array($cfg, $file));
                        if ( ! file_exists($configDir . "/$file")) {
                            trigger_error("Нет автозагрузочной секции конфигурации '$cfg'" . "/$file",
                                          E_USER_ERROR);
                            return;
                        }
                        return Utils::loadConfig($file);
                    };
                }
            }
        }
        unset($autoload);

        // Session
        $this->extend('session',
                      function($session, $c) {
            Log::debug('Session start', array(session_id()));
            return $session;
        });

        // Database
        // ------------------
        $this['db'] = function($c) {
            $db = \Fobia\DataBase\DbFactory::create($c['settings']['database']);
            \ezcDbInstance::set($db);
            return $db;
        };
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
     * Корневой путь приложухи (из конфига webpath)
     * @return string
     */
    public function getWebPath($url = null)
    {
        $url = $this->request->getUrl() . $this->config('webpath') . $url;
        return $url;
    }

    protected function defaultNotFound()
    {
        $this->halt(404, "Not Found");
    }

    // protected function defaultError($e)
    // {
    //     $this->contentType('text/html');
    //     dump($e);
    // }

    public function isCli()
    {
        defined('IS_CLI') or define('IS_CLI',  ! defined('SYSPATH'));
        return IS_CLI;
    }
}