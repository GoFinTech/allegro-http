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


use Exception;
use GoFinTech\Allegro\Http\HttpRequest;
use Psr\Log\LoggerInterface;
use RuntimeException;

class ApiServer
{
    private $log;
    private $server;

    public function __construct(LoggerInterface $log, int $port)
    {
        $this->log = $log;
        $this->server = stream_socket_server("tcp://0.0.0.0:$port", $errNo, $errStr);
        if (!$this->server)
            throw new RuntimeException("Failed to create listening socket on port $port: $errNo $errStr");
    }

    public function accept(): ?HttpRequest
    {
        $client = @stream_socket_accept($this->server, 5, $peer);
        if (!$client)
            return null;

        $this->log->debug("Received request from $peer");;

        try {
            return $this->readRequest($client, $peer);
        }
        catch (ApiServerException $ex) {
            $this->log->error($ex->getMessage());
        }
        catch (Exception $ex) {
            $this->log->error("Error reading client request", ["exception" => $ex]);
        }

        fwrite($client, "HTTP/1.0 400 Bad Request\r\nContent-Length: 0\r\n\r\n");
        fclose($client);
        return null;
    }

    private function readRequest($client, $peer): HttpRequest
    {
        $http = fgets($client);
        if (!preg_match('/^([^\s]+)\s+([^\s]+)\s+([^\s]+)\s*$/', $http, $match)) {
            throw new ApiServerException("Malformed first line");
        }

        $request = new HttpRequest();

        $request->method = $match[1];
        $request->uri = $match[2];
        $request->path = parse_url($request->uri, PHP_URL_PATH);
        $request->query = parse_url($request->uri, PHP_URL_QUERY);

        $headers = [];
        $lastHeader = null;

        while (!feof($client)) {
            $line = fgets($client);
            if (is_null($line))
                throw new ApiServerException("Incomplete request");
            if ($line == "\r\n" || $line == "\n")
                break;

            if (preg_match('/^([-_.a-zA-Z0-9]+): (.*)$/', $line, $match)) {
                $lastHeader = strtolower($match[1]);
                $headers[$lastHeader] = trim($match[2]);
            }
            else if (preg_match('/^\s(.*)$/', $line, $match) && $lastHeader) {
                $headers[$lastHeader] .= trim($match[1]);
            }
            else {
                throw new ApiServerException("Malformed headers");
            }
        }

        $request->host = $headers['host'] ?? '';
        if (empty($request->host))
            throw new ApiServerException("No host specified");

        $request->headers = new ArrayHeaderAccessor($headers);
        $request->cookies = new ArrayCookieAccessor([]); // TODO Cookies

        $request->input = $client;
        $request->output = new ApiServerOutput($client);

        return $request;
    }

    public function finish(HttpRequest $request): void
    {
        $request->output->write('');
        fclose($request->input);
    }
}
