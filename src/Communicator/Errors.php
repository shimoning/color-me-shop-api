<?php

namespace Shimoning\ColorMeShopApi\Communicator;

use Shimoning\ColorMeShopApi\Entities\Collection;
use Shimoning\ColorMeShopApi\Entities\Error;

class Errors extends Collection
{
    private Response $_response;

    public function __construct(Response $response, mixed $items = [])
    {
        parent::__construct($items);
        $this->_response = $response;
    }

    public function getResponse(): Response
    {
        return $this->_response;
    }

    static public function build(Response $response): self
    {
        return new self(
            $response,
            \array_map(function ($error) {
                return new Error($error);
            }, $response->getParsedBody()['errors'] ?? []),
        );
    }
}
