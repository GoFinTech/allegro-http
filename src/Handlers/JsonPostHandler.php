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
    public const DIRECT_RESPONSE = 'allegro:jsonpost:direct_response';

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

        // in case of a direct response we skip further request handling and writing output again
        if ($request->hasTag(self::DIRECT_RESPONSE))
            return;

        $this->writeResponse($request, $output);
    }

    protected function readRequest(HttpRequest $request, ?string $requestClass)
    {
        if ($request->method == 'get' || !$requestClass) {
            return null;
        }

        $reqLength = $request->headers->get('content-length');
        $maxLen = $request->http->getOption(HttpApp::OPTION_MAX_REQUEST_BODY);
        if (isset($reqLength)) {
          if ($reqLength <= $maxLen)
              $body = stream_get_contents($request->input, $reqLength);
          else
              throw HttpException::PayloadTooLarge();
        }
        else {
            $body = stream_get_contents($request->input, $maxLen);
            if (strlen($body) >= $maxLen)
                throw HttpException::PayloadTooLarge();
        }

        try {
            $input = $this->serializer->deserialize($body, $requestClass, 'json');
        }
        catch (Exception $ex) {
            throw new ApplicationException(get_class($ex) . ": " . $ex->getMessage(), 400);
        }

        return $input;
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
