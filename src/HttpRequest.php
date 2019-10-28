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


use GoFinTech\Allegro\Http\Implementation\ArrayCookieAccessor;
use GoFinTech\Allegro\Http\Implementation\CliOutputInterface;
use GoFinTech\Allegro\Http\Implementation\EnvVarHeaderAccessor;
use GoFinTech\Allegro\Http\Implementation\ServerOutputInterface;

class HttpRequest
{
    /** @var HttpApp */
    public $http;
    /** @var resource */
    public $input;
    /** @var HttpOutputInterface */
    public $output;

    /** @var string */
    public $method;
    /** @var string */
    public $host;
    /** @var string */
    public $uri;
    /** @var string */
    public $query;
    /** @var HeaderAccessorInterface */
    public $headers;
    /** @var CookieAccessorInterface */
    public $cookies;
    /** @var string */
    public $remoteAddress;

    /** @var RouteEntry */
    public $route;
    /** @var string */
    public $path;
    /** @var string */
    public $action;

    public static function fromEnv(): HttpRequest
    {
        $request = new HttpRequest();

        $request->method = strtolower($_SERVER['REQUEST_METHOD']);
        $request->host = $_SERVER['HTTP_HOST'];
        
        $request->uri = $_SERVER['REQUEST_URI'];
        $request->query = '';
        
        $path = $request->uri;
        $q = strpos($path, '?');
        if ($q !== false) {
            $request->query = substr($path, $q + 1);
            $path = substr($path, 0, $q);
        }
        $request->path = ltrim($path, '/');

        $request->headers = new EnvVarHeaderAccessor($_SERVER);
        $request->cookies = new ArrayCookieAccessor($_COOKIE);
        $request->remoteAddress = $_SERVER['REMOTE_ADDR'];

        $request->input = fopen('php://input', 'r');

        if (php_sapi_name() == 'cli') {
            $request->output = new CliOutputInterface();
        }
        else {
            $request->output = new ServerOutputInterface();
        }

        return $request;
    }
}
