<?php

namespace OrdinaryJellyfish\FlarumReact\Emitters;

use Psr\Http\Message\ResponseInterface;
use React\Http\Response;
use Zend\HttpHandlerRunner\Emitter\EmitterInterface;

class ReactEmitter implements EmitterInterface
{
    /**
     * @var Response
     */
    private $reactResponse;

    public function __construct(Response $response)
    {
        $this->reactResponse = $response;
    }

    public function emit(ResponseInterface $response): bool
    {
        $this->writeHead($response);
        $this->writeBody($response);

        return true;
    }

    public function writeHead(ResponseInterface $response)
    {
        $statusCode = $response->getStatusCode();
        $headers = $response->getHeaders();

        $this->reactResponse = $this->reactResponse->withStatus($statusCode);

        foreach ($headers as $key => $value) {
            $this->reactResponse = $this->reactResponse->withHeader($key, $value[0]);
        }
    }

    public function writeBody(ResponseInterface $response)
    {
        $this->reactResponse = $this->reactResponse->withBody($response->getBody());
    }

    public function getReactResponse()
    {
        return $this->reactResponse;
    }
}
