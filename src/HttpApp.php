<?php

/*
 * This file is part of the Allegro framework.
 *
 * (c) 2019-2021 Go Financial Technologies, JSC
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GoFinTech\Allegro\Http;


use Exception;
use GoFinTech\Allegro\AllegroApp;
use GoFinTech\Allegro\Http\Handlers\JsonServiceHandler;
use GoFinTech\Allegro\Http\Implementation\ApiServer;
use LogicException;
use Psr\Container\ContainerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Yaml\Yaml;

class HttpApp
{
    public const OPTION_MAX_REQUEST_BODY = "maxRequestBody";
    /**
     * Controls CORS handling.
     * - none (default): CORS is disabled and cross-origin requests will fail on browser side
     * - allow: allows all cross-origin requests without credentials
     */
    public const OPTION_CORS_MODE = "corsMode";
    /**
     * Specifies a header to deduce a real client IP address
     * - "" (default, empty): IP is taken directly from connection / http server environment
     * - name-of-header: a header that contains an actual IP address or is an X-Forwarded-For style header
     */
    public const OPTION_REALIP_HEADER = "realIpHeader";

    /** @var AllegroApp */
    private $app;
    /** @var string */
    private $appName;
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
            self::OPTION_CORS_MODE => 'none',
            self::OPTION_REALIP_HEADER => null,
        ];

        $this->loadConfiguration($app->getConfigLocator(), $configSection);
    }

    private function loadConfiguration(FileLocator $locator, string $configSection): void
    {
        $fileName = $locator->locate('http.yml');
        $config = Yaml::parseFile($fileName);

        $this->appName = $configSection;

        if (isset($config['options'])) {
            foreach ($config['options'] as $key => $value) {
                $this->options[$key] = $value;
            }
        }

        if (!isset($config[$configSection]))
            throw new LogicException("HttpApp: section $configSection is not defined in http.yml");

        $http = $config[$configSection];

        if (isset($http['options'])) {
            foreach ($http['options'] as $key => $value) {
                $this->options[$key] = $value;
            }
        }

        if (isset($http['routes'])) {
            /* Recommended config */
            $routes = $http['routes'];
        }
        else {
            /* Legacy ambiguous config */
            $routes = $http;
        }

        $router = new RequestRouter();
        $container = $this->app->getContainer();
        $sequence = 1;

        foreach ($routes as $path => $routeConfig) {
            $handler = $routeConfig['handler'] ?? null;
            if (is_null($handler))
                throw new LogicException("HttpApp: route $path of $configSection does not specify handler");
            if ($handler == '@jsonService') {
                $serviceName = "allegro.http.jsonService.$sequence";
                $container->register($serviceName, JsonServiceHandler::class)
                    ->setPublic(true)
                    ->addArgument($routeConfig['interface'])
                    ->addArgument(new Reference($routeConfig['service']));
                $router->add($path, $serviceName);
                $sequence++;
            }
            else {
                $router->add($path, $handler);
            }
        }

        $this->router = $router;

        $container->register(HttpRequest::class)->setSynthetic(true);
    }

    public function handleRequest(HttpRequest $request = null): void
    {
        $this->app->compile();

        if (is_null($request))
            $request = HttpRequest::fromEnv();

        $this->handleRealIp($request);
        $this->processRequest($request);
    }

    private function processRequest(HttpRequest $request): void
    {
        try {

            $this->app->getContainer()->set(HttpRequest::class, $request);

            $request->http = $this;

            $this->router->resolve($request);

            if ($this->handleCors($request))
                return;

            /** @noinspection PhpUnhandledExceptionInspection */
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

    public function startServer(int $port): void
    {
        $this->app->compile();

        $log = $this->app->getLogger();
        $log->info("Starting API mode server {$this->appName} on port $port");

        $server = new ApiServer($log, $port);

        while (true) {
            if ($this->app->isTermSignalReceived()) {
                $log->info('Performing graceful shutdown on SIGTERM');
                break;
            }

            $request = $server->accept();

            if (!$request)
                continue;

            $success = false;
            try {
                $this->handleRealIp($request);
                $log->info("IN {$request->remoteAddress} {$request->method} {$request->uri}");
                $this->processRequest($request);
                $success = true;
            }
            catch (Exception $ex) {
                $log->error('EX ' . get_class($ex) . ': ' . $ex->getMessage(), ['exception' => $ex]);
                if ($this->app->isExceptionDeadly($ex)) {
                    throw $ex;
                }
            }
            finally {
                if (!$success)
                    $server->fail($request);
                $server->finish($request);
            }
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
        /** @noinspection PhpUnhandledExceptionInspection */
        /** @var SerializerInterface $serializer */
        $serializer = $this->app->getContainer()->get('serializer');
        return $serializer;
    }

    /**
     * Handles CORS rules.
     * @param HttpRequest $request
     * @return bool TRUE if request has been handled
     */
    private function handleCors(HttpRequest $request): bool
    {
        $corsMode = $this->getOption(self::OPTION_CORS_MODE, 'none');

        if ($corsMode != 'allow')
            return false;

        $origin = $request->headers->get('Origin');

        if (!$origin)
            return false;

        if ($request->method == 'options') {
            $out = $request->output;
            $out->header('Vary: Origin');
            $out->header("Access-Control-Allow-Origin: $origin");
            $out->header('Access-Control-Allow-Methods: GET, HEAD, POST, PUT, PATCH, DELETE');
            $wantHeaders = $request->headers->get('Access-Control-Request-Headers');
            if ($wantHeaders)
                $out->header("Access-Control-Allow-Headers: $wantHeaders");
            $out->header('Access-Control-Max-Age: 86400');
            return true;
        }

        $request->output->header("Access-Control-Allow-Origin: $origin");
        return false;
    }

    /**
     * Extracts and overrides remoteAddress as configured in realIp* options.
     * @param HttpRequest $request
     */
    private function handleRealIp(HttpRequest $request): void
    {
        $realIpHeader = $this->getOption(self::OPTION_REALIP_HEADER);
        if (empty($realIpHeader))
            return;

        $realIpValue = $request->headers->get($realIpHeader);
        if (empty($realIpValue))
            return;

        // Direct IP address specification option
        if (preg_match('/^[0-9]{1,3}([.][0-9]{1,3}){3}$/', $realIpValue)) {
            $request->remoteAddress = $realIpValue;
            return;
        }

        // Assuming X-Forwarded-For style
        $forwardList = array_reverse(explode(',', $realIpValue));
        $takeNext = false;
        foreach ($forwardList as $item) {
            $item = trim($item);
            if ($takeNext) {
                $request->remoteAddress = $item;
                return;
            }
            if (preg_match('/^(10[.]|192[.]168[.]|172[.](1[6-9]|2[0-9]|3[0-1])[.]|127[.])/', $item)) {
                // Internal address - skip until external is found
                continue;
            }
            // External address is found - assume next one is the client unless you have multiple proxies with public IPs
            $takeNext = true;
        }
    }
}
