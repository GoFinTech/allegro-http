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
 * Provides access to request cookies.
 * @package GoFinTech\Allegro\Http
 */
interface CookieAccessorInterface
{
    /**
     * Returns cookie value.
     * @param string $name cookie name
     * @return string|null null is returned if cookie is not present
     */
    public function get(string $name): ?string;
}
