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

/**
 * Provides access to request headers.
 * TODO Handle multiple headers in a way that prevents abuse. I.e. code must explicitly handle multiple values.
 * @package GoFinTech\Allegro\Http
 */
interface HeaderAccessorInterface
{
    /**
     * Returns header value.
     * @param string $name case-insensitive header name
     * @return string|null null is returned if header is not present
     */
    public function get(string $name): ?string;
}
