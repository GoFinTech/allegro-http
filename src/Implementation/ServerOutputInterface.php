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
use GoFinTech\Logging\elog;

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

    public function cookie(string $name, string $value = "", ?int $ttlSeconds = null, ?array $options = null): void
    {
        if (is_null($ttlSeconds))
            $expires = 0;
        else if ($ttlSeconds <= 0) {
            $expires = 1;
        }
        else {
            $expires = time() + $ttlSeconds;
        }

        // cookie path defaults to / because otherwise the default path is ambiguous
        // and frankly, I thought it was like that by default :P

        if (PHP_VERSION_ID >= 70300) {
            // $options for setcookie are only available since 7.3
            $cookieOptions = ['expires' => $expires, 'path' => '/'];
            if (!empty($options))
                $cookieOptions = array_replace($cookieOptions, $options);
            setcookie($name, $value, $cookieOptions);
        }
        else {
            // emulation for 7.2
            $path = $options['path'] ?? '/';
            $domain = $options['domain'] ?? '';
            $secure = $options['secure'] ?? false;
            $httpOnly = $options['httponly'] ?? false;

            setcookie($name, $value, $expires, $path, $domain, $secure, $httpOnly);
        }
    }

    public function write($content): void
    {
        echo $content;
    }
}
