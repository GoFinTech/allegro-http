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


class RouteEntry
{
    private $path;
    private $service;

    private $prefix;

    public function __construct(string $path, string $service)
    {
        $this->path = (substr($path, 0, 1) == '/' ? $path : '/' . $path);
        $this->service = $service;

        $this->prefix = $this->path;
        if (substr($this->prefix, -1, 1) != '/')
            $this->prefix .= '/';
    }

    public function matches(string $path, &$action): bool
    {
        if (substr($path, 0, 1) != '/')
            $path = '/' . $path;

        if ($this->path != $path && substr($path, 0, strlen($this->prefix)) != $this->prefix)
            return false;

        $action = substr($path, strlen($this->prefix));

        return true;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getService(): string
    {
        return $this->service;
    }
}
