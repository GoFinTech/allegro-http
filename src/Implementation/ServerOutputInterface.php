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


use GoFinTech\Allegro\Http\HttpOutputInterface;

class ServerOutputInterface implements HttpOutputInterface
{
    public function setStatusCode(int $statusCode): void
    {
        http_response_code($statusCode);
    }

    public function header(string $header): void
    {
        header($header);
    }

    public function write($content): void
    {
        echo $content;
    }
}
