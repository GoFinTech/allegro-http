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


use GoFinTech\Allegro\Http\HttpException;
use GoFinTech\Allegro\Http\HttpRequest;
use LogicException;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Symfony\Component\Serializer\SerializerInterface;

class JsonServiceHandler extends JsonPostHandler
{
    public const DIRECT_RESPONSE = 'direct_response';

    /** @var string[] indexed by method name */
    private $requestTypes;
    /** @var object */
    private $service;

    public function __construct(string $interfaceName, object $service, ?SerializerInterface $serializer = null)
    {
        parent::__construct($serializer);

        try {
            $interfaceInfo = new ReflectionClass($interfaceName);
            $this->requestTypes = [];
            foreach ($interfaceInfo->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                $params = $method->getParameters();
                if (isset($params[0]))
                    $this->requestTypes[$method->name] = (string)$params[0]->getType();
                else
                    $this->requestTypes[$method->name] = '';
            }
        } catch (ReflectionException $ex) {
            throw new LogicException("JsonServiceHandler: can't initialize $interfaceName, {$ex->getMessage()}", 0, $ex);
        }
        $this->service = $service;
    }

    public function handleRequest(HttpRequest $request): void
    {
        // in case of a direct response we skip further request handling
        if (in_array(self::DIRECT_RESPONSE, $request->tags))
            return;

        $serviceMethod = $request->action;
        $requestType = $this->requestTypes[$serviceMethod] ?? null;
        if (!isset($requestType))
            throw HttpException::NotFound();

        $this->callAction($request, [$this->service, $serviceMethod], $requestType);
    }
}
