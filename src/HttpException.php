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

class HttpException extends RuntimeException
{
    /** @var int */
    private $statusCode;

    public function __construct(int $statusCode)
    {
        parent::__construct("HTTP $statusCode");
        $this->statusCode = $statusCode;
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public static function BadRequest(): HttpException { return new HttpException(400); }
    public static function Unauthorized(): HttpException { return new HttpException(401); }
    public static function Forbidden(): HttpException { return new HttpException(403); }
    public static function NotFound(): HttpException { return new HttpException(404); }
    public static function MethodNotAllowed(): HttpException { return new HttpException(405); }
    public static function NotAcceptable(): HttpException { return new HttpException(406); }
    public static function Conflict(): HttpException { return new HttpException(409); }
    public static function Gone(): HttpException { return new HttpException(410); }
    /** @obsolete Please use PreconditionFailed */
    public static function PreconfitionFailed() { return new HttpException(412); }
    public static function PreconditionFailed() { return new HttpException(412); }
    public static function PayloadTooLarge() { return new HttpException(413); }
    public static function InternalServerError() { return new HttpException(500); }
    public static function NotImplemented() { return new HttpException(501); }
    public static function ServiceUnavailable() { return new HttpException(503); }
}
