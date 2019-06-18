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


class RequestRouter
{
    /** @var RouteEntry[] */
    private $entries;

    public function __construct()
    {
        $this->entries = [];
    }

    public function add(string $path, string $service): void
    {
        $this->entries[] = new RouteEntry($path, $service);
    }

    public function resolve(HttpRequest $request): void
    {
        $path = $request->path;

        /** @var RouteEntry $entry */
        foreach ($this->entries as $entry) {
            if ($entry->matches($path, $action)) {
                $request->route = $entry;
                $request->action = $action;
                return;
            }
        }

        throw HttpException::NotFound();
    }
}
