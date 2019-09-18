<?php

/*
 * This file is part of the Allegro framework.
 *
 * (c) 2019 Go Financial Technologies, JSC
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GoFinTech\Allegro\Http\Implementation;


use GoFinTech\Allegro\Http\CookieAccessorInterface;

class ArrayCookieAccessor implements CookieAccessorInterface
{
    /** @var string[] */
    private $cookies;
    
    public function __construct(array $cookies)
    {
        $this->cookies = $cookies;
    }

    public function get(string $name): ?string
    {
        return $this->cookies[$name] ?? null;
    }
}
