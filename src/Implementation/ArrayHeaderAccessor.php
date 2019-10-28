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


use GoFinTech\Allegro\Http\HeaderAccessorInterface;

class ArrayHeaderAccessor implements HeaderAccessorInterface
{
    private $headers;

    /**
     * ArrayHeaderAccessor constructor.
     * @param string[] $headers ['name' => 'value' ] with name in lowercase
     */
    public function __construct(array $headers)
    {
        $this->headers = $headers;
    }

    public function get(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }
}
