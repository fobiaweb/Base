<?php

namespace Fobia\Base;

/**
 * Вспомогательные утилиты
 * @package Fobia.Base
 */
class Utils
{

    /**
     * Загрузить файл конфигурации из директории CONFIG_DIR либо абсолютного пути
     * (php, ini, json, yml)
     *
     * @param string $file
     * @return mixed
     */
    public static function loadConfig($file, $format = null)
    {
        // if (substr($file, 0, 1) !== '/') {
        //     if (defined('CONFIG_DIR')) {
        //         $file = CONFIG_DIR . '/' . $file;
        //     }
        // }
        if (!$format) {
            $format = pathinfo($file, PATHINFO_EXTENSION);
        }

        if (!file_exists($file)) {
            trigger_error("Не найден конфигурационый файла '{$file}'.", E_USER_ERROR);
            return false;
        }

        $config = false;
        switch ($format) {
            case 'php':
                $config = include $file;
                break;
            case 'ini':
            case 'conf':
                $config = parse_ini_file($file);
                break;
            case 'yml':
                $config = \Symfony\Component\Yaml\Yaml::parse($file);
                break;
            case 'json':
                $config = \CJSON::decode(file_get_contents($file));
                break;
            case 'cache':
                $config = @unserialize(file_get_contents($file));
                break;
            default :
                trigger_error("Неизвестный формат конфигурационого файла '$format'.", E_USER_ERROR);
                break;
        }

        return $config;
    }

    /**
     * Загрузка из кеша
     *
     * @param string $file
     * @param string $dir
     * @return mixed
     */
    public static function loadConfigCache($file, $dir = null)
    {
        if ($dir == null) {
            if (defined('CACHE_DIR')) {
                $dir = CACHE_DIR;
            } else {
                return self::loadConfig($file);
            }
        }

        $cache_file = $dir . '/' . basename($file) . '.cache';
        if (file_exists($cache_file)) {
            if ((filemtime($cache_file) - filemtime($file)) > 0) {
                $config = unserialize(file_get_contents($cache_file));
                return $config;
            } else {
                // unlink($cache_file);
            }
        }

        $config = self::loadConfig($file);
        if (pathinfo($file, PATHINFO_EXTENSION) !== 'php') {
            file_put_contents($cache_file, serialize($config));
        }
        return $config;
    }

    /**
     * Трасировка в латинские символы.
     *
     * @param string $str
     * @return string
     */
    public static function letterTrans($str)
    {
        $arr = array(
            'а' => 'a', 'А' => 'A',
            'б' => 'b', 'Б' => 'B',
            'в' => 'v', 'В' => 'V',
            'г' => 'g', 'Г' => 'G',
            'д' => 'd', 'Д' => 'D',
            'е' => 'e', 'Е' => 'E',
            'ж' => 'g', 'Ж' => 'G',
            'з' => 'z', 'З' => 'Z',
            'и' => 'i', 'И' => 'I',
            'й' => 'y', 'Й' => 'Y',
            'к' => 'k', 'К' => 'K',
            'л' => 'l', 'Л' => 'L',
            'м' => 'm', 'М' => 'M',
            'н' => 'n', 'Н' => 'N',
            'о' => 'o', 'О' => 'O',
            'п' => 'p', 'П' => 'P',
            'р' => 'r', 'Р' => 'R',
            'с' => 's', 'С' => 'S',
            'т' => 't', 'Т' => 'T',
            'у' => 'u', 'У' => 'U',
            'ф' => 'f', 'Ф' => 'F',
            'х' => 'h', 'Х' => 'H',
            'ы' => 'i', 'Ы' => 'I',
            'э' => 'e', 'Э' => 'E',

            'ё' => "yo",   'Ё' => "Yo",
            "ц" => "ts",   'Ц' => "Ts",
            'ц' => "ts",   'Ц' => "Ts",
            'ч' => "ch",   'Ч' => "Ch",
            'ш' => "sh",   'Ш' => "Sh",
            'щ' => "shch", 'Щ' => "Shch",
            'ъ' => '',     'Ъ' => '',
            'ь' => '',     'Ь' => '',
            'ю' => "yu",   'Ю' => "Yu",
            'я' => "ya",   'Я' => "Ya",
        );

        $str = str_replace(array_keys($arr), array_values($arr), $str);
        return $str;
    }

    /**
     * Время работы скрипта.
     *
     * @param float $end
     * @param float $start
     * @return float
     */
    public static function getExecutionTime($end = null, $start = null)
    {
        $start = ($start !== null) ? $start : $_SERVER["REQUEST_TIME_FLOAT"] ;
        $end   = ($end   !== null) ? $end : microtime(true) ;
        return $end - $start;
    }

    /**
     * Объем занимаемой памяти в байтах
     *
     * @return integer объем занимаемой памяти
     */
    public static function getMemoryUsage($format = false)
    {
        if (function_exists('memory_get_usage')) {
            $bytes = memory_get_usage();
        } else {
            $output = array();
            if (strncmp(PHP_OS, 'WIN', 3) === 0) {
                exec('tasklist /FI "PID eq ' . getmypid() . '" /FO LIST',
                     $output);
                $bytes = isset($output[5]) ? preg_replace('/[\D]/', '', $output[5]) * 1024 : 0;
            } else {
                $pid    = getmypid();
                exec("ps -eo%mem,rss,pid | grep $pid", $output);
                $output = explode("  ", $output[0]);
                $bytes = isset($output[1]) ? $output[1] * 1024 : 0;
            }
        }

        if ($format) {
            $bytes = self::formatBytes($bytes);
        }
        return $bytes;
    }


    /**
     * Formats bytes into a human readable string (Объем памяти в единицах измерения)
     *
     * @param  int    $bytes
     * @return string
     */
    public static function formatBytes($bytes)
    {
        // $unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
        // $str  = @round($size / pow(1024, ($i    = floor(Log($size, 1024)))), 2) . ' ' . $unit[$i];
        // return $str;

        $bytes = (int) $bytes;

        if ($bytes > 1024*1024) {
            // return sprintf("%8s", round($bytes/1024/1024, 2).'MB');
            return sprintf("%s", round($bytes/1024/1024, 2).'MB');
        } elseif ($bytes > 1024) {
            //return sprintf("%8s",round($bytes/1024, 2).'KB');
            return sprintf("%s",round($bytes/1024, 2).'KB');
        }

        //return sprintf("%8s", $bytes . 'B');
        return sprintf("%s", $bytes . 'B');
    }

    /**
     * Возвращает ресурсы (время, память) в связи с запросом в виде строки.
     *
     * @return string
     */
    public static function resourceUsage()
    {
        $ms = self::getExecutionTime();
        return sprintf('Memory usage: %4.2fMB (peak: %4.2fMB), time: %6.4f',
                memory_get_usage() / 1048576,
                memory_get_peak_usage(true) / 1048576,
                $ms);
    }

    /**
     * IP клиента
     *
     * @return string
     */
    public static function GetIp()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    /**
     * Уникальный индетификатор пользователя
     *
     * @return string
     */
    public static function GetPPID()
    {
        // $ppid =  shell_exec('echo "$PPID"').PHP_EOL;
        // echo $ppid.PHP_EOL;
        $ps = shell_exec('ps -o uid= -o ppid= -p "$PPID"');
        $sid = 'sid-'.preg_replace('/ +/', '-', trim($ps));
        return $sid;
    }

    /**
     * Перенаправляет на страницу. Автоматически добовляет параметр r=XX произвольного значения,
     * вне зависимости имееться он или нет. При отсутствии целевой страници page,
     * переадрисуеться на туже страницу с теме же параметрами с изменеыми на params_arr,
     * если таковы имеються.
     *
     * @param string $page целевая страница
     * @param array $data параметры get
     */
    public static function location($page = "", $data = false)
    {
        if (!is_array($data)) {
            $data = array();
        }

        if ($page == "") {
            $page = substr($_SERVER["SCRIPT_NAME"], 1);
            $url  = $_SERVER["QUERY_STRING"];

            $key_value = explode("&", $url);
            foreach ($key_value as $value) {
                $kv = explode("=", $value);
                if ($kv[0] && $kv[1]) {
                    if (!$data[$kv[0]]) {
                        $data[$kv[0]] = $kv[1];
                    }
                }
            }
        }
        if ($page == "/")
            $page = "";

        unset($data["r"]);
        $data["r"] = rand();

        $get = "";

        foreach ($data as $key => $value) {
            $get .= $key . "=" . $value . "&";
        }

        $get    = substr($get, 0, -1);
        $header = "Location: http://" . $_SERVER["HTTP_HOST"] . "/{$page}?{$get}";
        header($header);
        exit();
    }

    /**
     * Парсирует строку URL
     *
     * @param string $url
     * @param array $options
     * @return type
     */
    public static function parseURL($url, array $options = array())
    {
        if (isset($options['first'])) {
            $first = $options['first'];
        }
        if (isset($options['last'])) {
            $last = $options['last'];
        }

        $trim = (isset($options['trim'])) ? true : false;
        $dr   = (isset($options['dr'])) ? $options['dr'] : DIRECTORY_SEPARATOR;

        $pattern     = array(
            "#[\\\/]+#",
            "#^[\\\/]+#",
            "#[\\\/]+$#"
        );
        $replacement = array($dr);
        if ($trim) {
            $replacement[] = (($first) ? $dr : '');
            $replacement[] = (($last) ? $dr : '');
        }

        $url = preg_replace($pattern, $replacement, $url);
        return $url;
    }

    /**
     * декодирует URL-кодированную строку
     *
     * @param array|string $data
     * @return mixed
     */
    public static function URLDecode($data)
    {
        if (!is_array($data)) {
            $data = urldecode($data);
            return $data;
        }
        reset($data);
        while (list($key, $value) = each($data)) {
            if (is_array($value)) {
                $data[$key] = self::URLDecode($value);
            } else {
                $data[$key] = urldecode($value);
            }
        }
        return $data;
    }

    /**
     * Парсирует шаблон из файла. Замеяет переменные в шаблоне.
     *
     * @param string $file имя шаблона
     * @param array $vars масив переменных вставляемые в шаблон
     * @return string преобразовынный шаблон
     */
    public static function parseTemplateFile($file, $vars = false)
    {
        $string = @file_get_contents($file);

        if ($string) {
            $string = self::parseTemplateString($string, $vars);
        } //return

        return $string;
    }

    /**
     * Парсирует шаблон строки. Замеяет переменные в шаблоне.
     *
     * @param string $string строка
     * @param array $vars масив переменных вставляемые в шаблон
     * @return string преобразовынный шаблон
     * @version 0.2
     */
    public static function parseTemplateString($string, $vars = false)
    {
        if (preg_match_all("/{([A-Z]+[\d_A-Z]*)}/", $string, $regs)) {
            $regs = $regs[1];
            foreach ($regs as $key) {
                $key        = strtolower($key);
                $vars[$key] = $vars[$key];
            }
        }

        if (is_array($vars)) {
            reset($vars);
            while (list($key, $value) = each($vars)) {
                $string = str_replace("{" . strtoupper($key) . "}", $value, $string);
            }
        }

        return $string;
    }

    /**
     * Функция генерирует пароль
     *
     * @param int $number количество символов в пароле.
     * @return string
     */
    public static function randString($number = 10)
    {
        $chr  = "abvgdegziyklmnoprstufieABVGDEGZIYKLMNOPRSTUFIE1234567890";
        $pass = "";
        for ($i = 0; $i < $number; $i++) {
            $index = rand(0, strlen($chr));
            $pass .= substr($chr, $index, 1);
        }
        return $pass;
    }

    /**
     * Устанавливает константу
     *
     * @param string $name
     * @param string $value
     * @return bool возвращает FALSE если уже константа установлена
     */
    public static function def($name, $value)
    {
        if (!defined($name)) {
            define($name, $value);
            return true;
        }
        return false;
    }

    /**
     * Создает новый объект экземпляр класса
     *
     * @param string $class class name
     * @param mixed ... arguments constructor
     *
     * @return mixed new class
     */
    public static function createClass($class) {
        $args = func_get_args();
        array_shift($args);

        $reflectionClass = new \ReflectionClass($class);
        $instance = $reflectionClass->newInstanceArgs($args);
        return $instance;
    }

    /**
     * Проверяет подключен ли файл
     *
     * @param string $file __FILE__ or markName
     * @param bool $add добавить в список подключаемых
     * @return bool TRUE если файл уже был подключен
     */
    public static function isRequire($file, $add = true)
    {
        static $files = array();

        if (in_array($file, $files)) {
            return true;
        } else {
            if ($add) {
                $files[] = $file;
            }
            return false;
        }
    }
}
