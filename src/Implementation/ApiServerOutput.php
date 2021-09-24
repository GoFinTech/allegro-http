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


use ErrorException;
use GoFinTech\Allegro\Http\HttpOutputInterface;
use LogicException;
use Psr\Log\LoggerInterface;

class ApiServerOutput implements HttpOutputInterface
{
    private static $HTTP_STATUS_TEXT = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        103 => 'Early Hints',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => "I'm a teapot",
        421 => 'Misdirected Request',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Too Early',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
    ];

    /** @var resource */
    private $client;
    /** @var LoggerInterface */
    private $log;
    /** @var int */
    private $statusCode;
    /** @var string[] */
    private $headers;

    /** @var bool */
    private $headersSent;

    public function __construct($client, $log)
    {
        $this->client = $client;
        $this->log = $log;
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
        $str = "$name=$value";
        $par = [
            'path=' => '/',
        ];
        if (is_int($ttlSeconds)) {
            if ($ttlSeconds <= 0)
                $par['max-age='] = -1;
            else
                $par['max-age='] = $ttlSeconds;
        }
        if (!empty($options)) {
            foreach ($options as $key => $value) {
                $key = strtolower($key);
                switch ($key) {
                    case 'secure':
                    case 'httponly':
                        if ($value)
                            $par[$key] = '';
                        break;
                    default:
                        $par["$key="] = $value;
                        break;
                }
            }
        }
        foreach ($par as $key => $value) {
            $str .= '; ' . $key . $value;
        }
        $this->header("Set-Cookie: $str");
    }

    public function write($content): void
    {
        if (!$this->headersSent) {
            $this->headersSent = true;
            $text = self::$HTTP_STATUS_TEXT[$this->statusCode] ?? "Unknown";
            fwrite($this->client, "HTTP/1.0 {$this->statusCode} $text\r\n");
            $this->log->info("OUT {$this->statusCode}");
            foreach ($this->headers as $header) {
                fwrite($this->client, "$header\r\n");
            }
            fwrite($this->client, "\r\n");
        }
        fwrite($this->client, $content);
    }

    public function fail(): void
    {
        if ($this->headersSent)
            return;

        $this->headers = [];
        $this->statusCode = 500;
    }
}
