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


use RuntimeException;

class ApplicationException extends RuntimeException implements HttpOutputProducerInterface
{
    /** @var int */
    private $statusCode;

    public function __construct(string $message, int $statusCode = 500)
    {
        parent::__construct($message);
        $this->statusCode = $statusCode;
    }

    public function sendOutput(HttpOutputInterface $output)
    {
        $output->setStatusCode($this->statusCode);
        $output->header('Content-Type: application/json');
        $output->write(json_encode(['message' => $this->getMessage()]));
    }
}
