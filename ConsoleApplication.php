<?php
/**
 * ConsoleApplication class  - ConsoleApplication.php file
 *
 * @author     Dmitriy Tyurin <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2014 Dmitriy Tyurin
 */

namespace Fobia\Base;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * ConsoleApplication class
 *
 * @package Fobia.Base
 */
class ConsoleApplication extends Application
{

    protected function getDefaultInputDefinition()
    {
        return new InputDefinition(array(
            new InputArgument('command', InputArgument::REQUIRED, 'команда для выполнения'),

            new InputOption('--help',           '-h', InputOption::VALUE_NONE, 'показать эту справку'),
            new InputOption('--quiet',          '-q', InputOption::VALUE_NONE, 'тихий (нет вывода).'),
            new InputOption('--verbose',        '-v|vv|vvv', InputOption::VALUE_NONE, 'Информативность сообщений'),
            new InputOption('--version',        '-V', InputOption::VALUE_NONE, 'заценить версию приложения.'),
            new InputOption('--profile',        null, InputOption::VALUE_NONE, 'Показать временя и используемую память'),
        ));
    }

}