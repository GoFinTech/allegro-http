<?php

/*
 * This file is part of the Allegro framework.
 *
 * (c) 2019 Go Financial Technologies, JSC
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GoFinTech\Allegro\Http;


use GoFinTech\Allegro\Http\Implementation\CliOutputInterface;
use GoFinTech\Allegro\Http\Implementation\ServerOutputInterface;

class HttpRequest
{
    /** @var HttpApp */
    public $http;
    /** @var HttpOutputInterface */
    public $output;
    /** @var string */
    public $path;
    /** @var string */
    public $method;
    /** @var RouteEntry */
    public $route;
    /** @var string */
    public $action;

    public static function fromEnv(): HttpRequest
    {
        $request = new HttpRequest();

        $path = $_SERVER['REQUEST_URI'];
        $q = strpos($path, '?');
        if ($q !== false) {
            $path = substr($path, 0, $q);
        }
        $request->path = ltrim($path, '/');

        $request->method = strtolower($_SERVER['REQUEST_METHOD']);

        if (php_sapi_name() == 'cli') {
            $request->output = new CliOutputInterface();
        }
        else {
            $request->output = new ServerOutputInterface();
        }

        return $request;
    }
}
