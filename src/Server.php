<?php

namespace OrdinaryJellyfish\FlarumReact;

use Flarum\Foundation\SiteInterface;
use OrdinaryJellyfish\FlarumReact\Emitters\ReactEmitter;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Response as ReactResponse;
use Throwable;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use Zend\HttpHandlerRunner\RequestHandlerRunner;
use Zend\Stratigility\Middleware\ErrorResponseGenerator;

class Server
{
    /**
     * @var ReactEmitter
     */
    private $responseEmitter;

    private $site;

    public function __construct(SiteInterface $site)
    {
        $this->site = $site;
    }

    public function listen(ServerRequestInterface $request, ReactResponse $response)
    {
        $requestHandler = function () use ($request) {
            return $request;
        };
        $this->responseEmitter = new ReactEmitter($response);

        $runner = new RequestHandlerRunner(
            $this->safelyBootAndGetHandler(),
            $this->responseEmitter,
            $requestHandler,
            function (Throwable $e) {
                $generator = new ErrorResponseGenerator();

                return $generator($e, new ServerRequest(), new Response());
            }
        );
        $runner->run();
    }

    public function getResponse()
    {
        return $this->responseEmitter->getReactResponse();
    }

    private function safelyBootAndGetHandler()
    {
        try {
            return $this->site->bootApp()->getRequestHandler();
        } catch (Throwable $e) {
            exit($this->formatBootException($e));
        }
    }

    /**
     * Display the most relevant information about an early exception.
     */
    private function formatBootException(Throwable $error): string
    {
        $message = $error->getMessage();
        $file = $error->getFile();
        $line = $error->getLine();
        $type = get_class($error);

        return <<<ERROR
            Flarum encountered a boot error ({$type})<br />
            <b>{$message}</b><br />
            thrown in <b>{$file}</b> on line <b>{$line}</b>
ERROR;
    }
}
