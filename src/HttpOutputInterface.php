<?php

/*
 * This file is part of the Allegro framework.
 *
 * (c) 2019-2021 Go Financial Technologies, JSC
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GoFinTech\Allegro\Http;


interface HttpOutputInterface
{
    public function setStatusCode(int $statusCode): void;
    public function header(string $header): void;

    /** Send a Set-Cookie header.
     * @param string $name cookie name
     * @param string $value cookie value
     * @param int|null $ttlSeconds cookie expiration: null - default (session), 0 or negative - delete cookie
     * @param array|null $options additional cookie options like path, domain, secure, etc...
     */
    public function cookie(string $name, string $value = "", ?int $ttlSeconds = null, ?array $options = null): void;
    public function write($content): void;
}
