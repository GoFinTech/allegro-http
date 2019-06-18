<?php

/*
 * This file is part of the Allegro framework.
 *
 * (c) 2019 Go Financial Technologies, JSC
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GoFinTech\Allegro\Http\Handlers;


use Exception;
use GoFinTech\Allegro\Http\ApplicationException;
use GoFinTech\Allegro\Http\HttpApp;
use GoFinTech\Allegro\Http\HttpException;
use GoFinTech\Allegro\Http\HttpRequest;
use GoFinTech\Allegro\Http\RequestHandlerInterface;
use Symfony\Component\Serializer\SerializerInterface;

abstract class JsonPostHandler implements RequestHandlerInterface
{
    /** @var ?SerializerInterface */
    private $serializer;

    protected function __construct(?SerializerInterface $serializer = null)
    {
        $this->serializer = $serializer;
    }

    protected function callAction(HttpRequest $request, callable $method, ?string $requestClass)
    {
        if (!isset($this->serializer)) {
            $this->serializer = $request->http->getSerializer();
        }

        $input = $this->readRequest($request, $requestClass);
        $output = call_user_func($method, $input);
        unset($input);
        $this->writeResponse($request, $output);
    }

    protected function readRequest(HttpRequest $request, ?string $requestClass)
    {
        if ($request->method == 'get' || !$requestClass) {
            return null;
        }

        $in = fopen('php://input', 'r');
        try {
            $maxLen = $request->http->getOption(HttpApp::OPTION_MAX_REQUEST_BODY);
            $body = stream_get_contents($in, $maxLen);
            $next = stream_get_contents($in, 1);
            if ($next)
                throw HttpException::PayloadTooLarge();

            try {
                $input = $this->serializer->deserialize($body, $requestClass, 'json');
            }
            catch (Exception $ex) {
                throw new ApplicationException(get_class($ex) . ": " . $ex->getMessage(), 400);
            }

            return $input;
        }
        finally {
            fclose($in);
        }
    }

    protected function writeResponse(HttpRequest $request, $body): void
    {
        if (is_null($body)) {
            $request->output->setStatusCode(204);
            return;
        }

        $request->output->header('Content-Type: application/json');
        $data = $this->serializer->serialize($body, 'json');
        $request->output->write($data);
    }
}
