<?php

namespace Shimoning\ColorMeShopApi\Communicator;

class RequestMeta
{
    private string $_method;
    private string $_uri;
    private array $_options;

    public function __construct(
        string $method,
        string $uri,
        array $options,
    ) {
        $this->_method = $method;
        $this->_uri = $uri;
        $this->_options = $options;
    }

    public function getMethod(): string
    {
        return $this->_method;
    }
    public function getUri(): string
    {
        return $this->_uri;
    }
    public function getOptions(): array
    {
        return $this->_options;
    }
}
