<?php

/*
 * This file is part of the Allegro framework.
 *
 * (c) 2019-2021 Go Financial Technologies, JSC
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GoFinTech\Allegro\Http\Implementation;


use GoFinTech\Allegro\Http\HttpOutputInterface;

class CliOutput implements HttpOutputInterface
{
    /** @var bool */
    private $status;
    /** @var bool */
    private $writing;

    public function setStatusCode(int $statusCode): void
    {
        echo "HTTP $statusCode\n";
        $this->status = true;
    }

    public function header(string $header): void
    {
        $this->defaultStatus();
        echo "$header\n";
    }

    public function cookie(string $name, string $value = "", ?int $ttlSeconds = null, ?array $options = null): void 
    {
        echo "Cookie: $name=$value ttl=$ttlSeconds options=" . print_r($options, true) . "\n";
    }

    public function write($content): void
    {
        if (!$this->writing) {
            $this->writing = true;
            $this->defaultStatus();
            echo "\n";
        }
        echo $content;
    }

    private function defaultStatus(): void
    {
        if (!$this->status) {
            echo "HTTP 200 (assumed)\n";
            $this->status = true;
        }
    }
}
