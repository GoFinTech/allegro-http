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

    public function __construct(string $path, string $service)
    {
        $this->path = $path;
        $this->service = $service;
    }

    public function matches(string $path, &$action): bool
    {
        $prefix = $this->path . '/';
        if ($this->path != $path && substr($path, 0, strlen($prefix)) != $prefix)
            return false;

        $action = substr($path, strlen($prefix));

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
