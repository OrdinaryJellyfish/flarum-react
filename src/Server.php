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

    /**
     * @var ReactResponse
     */
    private $errorResponse;

    /**
     * @var ServerRequestInterface
     */
    private $request;

    private $site;

    public function __construct(ServerRequestInterface $request, SiteInterface $site)
    {
        $this->request = $request;
        $this->site = $site;
    }

    public function listen()
    {
        $requestHandler = function () {
            return $this->request;
        };
        $this->responseEmitter = new ReactEmitter();

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

    public function getResponse(): ReactResponse
    {
        return $this->errorResponse ?? $this->responseEmitter->getReactResponse();
    }

    private function safelyBootAndGetHandler()
    {
        try {
            return $this->site->bootApp()->getRequestHandler();
        } catch (Throwable $e) {
            $this->errorResponse = new ReactResponse(500, ['Content-Type' => 'text/html'], $this->formatBootException($e));
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
