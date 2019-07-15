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


use GoFinTech\Allegro\AllegroApp;
use GoFinTech\Allegro\Http\Handlers\JsonServiceHandler;
use LogicException;
use Psr\Container\ContainerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Yaml\Yaml;

class HttpApp
{
    public const OPTION_MAX_REQUEST_BODY = "maxRequestBody";

    /** @var AllegroApp */
    private $app;
    /** @var array */
    private $options;
    /** @var RequestRouter */
    private $router;

    public function __construct(AllegroApp $app, string $configSection)
    {
        $this->app = $app;

        $this->app->getContainer()->setParameter('allegro.console_logger.force_stderr', true);

        $this->options = [
            self::OPTION_MAX_REQUEST_BODY => 1048576,
        ];

        $this->loadConfiguration($app->getConfigLocator(), $configSection);
    }

    private function loadConfiguration(FileLocator $locator, string $configSection): void
    {
        $fileName = $locator->locate('http.yml');
        $config = Yaml::parseFile($fileName);

        if (isset($config['options'])) {
            foreach ($config['options'] as $key => $value) {
                $this->options[$key] = $value;
            }
        }

        $http = $config[$configSection];

        if (isset($http['options'])) {
            foreach ($http['options'] as $key => $value) {
                $this->options[$key] = $value;
            }
        }

        $router = new RequestRouter();
        $container = $this->app->getContainer();
        $sequence = 1;

        foreach ($http as $path => $routeConfig) {
            $handler = $routeConfig['handler'];
            if ($handler == '@jsonService') {
                $serviceName = "allegro.http.jsonService.$sequence";
                $container->register($serviceName, JsonServiceHandler::class)
                    ->setPublic(true)
                    ->addArgument($routeConfig['interface'])
                    ->addArgument($routeConfig['service']);
                $router->add($path, $serviceName);
                $sequence++;
            }
            else {
                $router->add($path, $handler);
            }
        }

        $this->router = $router;
    }

    public function handleRequest(HttpRequest $request = null): void
    {
        $this->app->compile();

        try {
            if (is_null($request))
                $request = HttpRequest::fromEnv();

            $request->http = $this;

            $this->router->resolve($request);

            /** @var RequestHandlerInterface $handler */
            $handler = $this->app->getContainer()->get($request->route->getService());

            $handler->handleRequest($request);
        }
        catch (HttpException $ex) {
            $request->output->setStatusCode($ex->statusCode());
        }
        catch (HttpOutputProducerInterface $ex) {
            $ex->sendOutput($request->output);
        }
    }

    public function getOption(string $option, $default = null)
    {
        if (array_key_exists($option, $this->options))
            return $this->options[$option];

        if (func_num_args() == 1)
            throw new LogicException("HttpApp: option $option is not defined");
        else
            return $default;
    }

    public function getContainer(): ContainerInterface
    {
        return $this->app->getContainer();
    }

    public function getSerializer(): SerializerInterface
    {
        /** @var SerializerInterface $serializer */
        $serializer = $this->app->getContainer()->get('serializer');
        return $serializer;
    }
}
