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
use LogicException;

class ApiServerOutput implements HttpOutputInterface
{
    /** @var resource */
    private $client;
    /** @var int */
    private $statusCode;
    /** @var string[] */
    private $headers;

    /** @var bool */
    private $headersSent;

    public function __construct($client)
    {
        $this->client = $client;
        $this->statusCode = 200;
        $this->headers = [];
        $this->headersSent = false;
    }

    public function setStatusCode(int $statusCode): void
    {
        if ($this->headersSent)
            throw new LogicException("AllegroHttp ApiServerOutput::setStatusCode: Too late, headers have been sent");
        $this->statusCode = $statusCode;
    }

    public function header(string $header): void
    {
        if ($this->headersSent)
            throw new LogicException("AllegroHttp ApiServerOutput::header: Too late, headers have been sent");
        $this->headers[] = $header;
    }

    public function cookie(string $name, string $value = "", ?int $ttlSeconds = null, ?array $options = null): void
    {
        // TODO: Implement cookie() method.
    }

    public function write($content): void
    {
        if (!$this->headersSent) {
            $this->headersSent = true;
            switch ($this->statusCode) {
                case 100: $text = 'Continue'; break;
                case 101: $text = 'Switching Protocols'; break;
                case 200: $text = 'OK'; break;
                case 201: $text = 'Created'; break;
                case 202: $text = 'Accepted'; break;
                case 203: $text = 'Non-Authoritative Information'; break;
                case 204: $text = 'No Content'; break;
                case 205: $text = 'Reset Content'; break;
                case 206: $text = 'Partial Content'; break;
                case 300: $text = 'Multiple Choices'; break;
                case 301: $text = 'Moved Permanently'; break;
                case 302: $text = 'Moved Temporarily'; break;
                case 303: $text = 'See Other'; break;
                case 304: $text = 'Not Modified'; break;
                case 305: $text = 'Use Proxy'; break;
                case 400: $text = 'Bad Request'; break;
                case 401: $text = 'Unauthorized'; break;
                case 402: $text = 'Payment Required'; break;
                case 403: $text = 'Forbidden'; break;
                case 404: $text = 'Not Found'; break;
                case 405: $text = 'Method Not Allowed'; break;
                case 406: $text = 'Not Acceptable'; break;
                case 407: $text = 'Proxy Authentication Required'; break;
                case 408: $text = 'Request Time-out'; break;
                case 409: $text = 'Conflict'; break;
                case 410: $text = 'Gone'; break;
                case 411: $text = 'Length Required'; break;
                case 412: $text = 'Precondition Failed'; break;
                case 413: $text = 'Request Entity Too Large'; break;
                case 414: $text = 'Request-URI Too Large'; break;
                case 415: $text = 'Unsupported Media Type'; break;
                case 500: $text = 'Internal Server Error'; break;
                case 501: $text = 'Not Implemented'; break;
                case 502: $text = 'Bad Gateway'; break;
                case 503: $text = 'Service Unavailable'; break;
                case 504: $text = 'Gateway Time-out'; break;
                case 505: $text = 'HTTP Version not supported'; break;
                default: $text = "Unknown"; break;
            }
            fwrite($this->client, "HTTP/1.0 {$this->statusCode} $text\r\n");
            foreach ($this->headers as $header) {
                fwrite($this->client, "$header\r\n");
            }
            fwrite($this->client, "\r\n");
        }
        fwrite($this->client, $content);
    }
}
