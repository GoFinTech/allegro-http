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

class EnvVarHeaderAccessor implements HeaderAccessorInterface
{
    /** @var string[] */
    private $vars;

    public function __construct(array $vars)
    {
        $this->vars = $vars;
    }

    public function get(string $name): ?string
    {
        $var = strtoupper($name);
        $var = str_replace('-', '_', $var);
        $var = "HTTP_$var";
        return $this->vars[$var] ?? null;
    }
}
