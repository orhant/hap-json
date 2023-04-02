<?php
/*
 * @copyright 2019-2020 hap http://hap.org
 * @author Igor A Tarasov <develop@hap.org>
 * @license MIT
 * @version 10.11.20 02:22:37
 */

/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

/** string */
const YII_ENV = 'dev';

/** bool */
const YII_DEBUG = true;

require_once(dirname(__DIR__) . '/vendor/autoload.php');
require_once(dirname(__DIR__) . '/vendor/yiisoft/yii2/Yii.php');

new yii\console\Application([
    'id' => 'test',
    'basePath' => dirname(__DIR__),
    'components' => [
        'cache' => yii\caching\FileCache::class,
        'log' => [
            'targets' => [
                [
                    'class' => yii\log\FileTarget::class,
                    'levels' => ['error', 'warning', 'info', 'trace']
                ]
            ]
        ],
        'urlManager' => [
            'hostInfo' => 'https://hap.org'
        ]
    ],
    'bootstrap' => ['log']
]);
