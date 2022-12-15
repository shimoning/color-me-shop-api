<?php

namespace Shimoning\ColorMeShopApi\Communicator;

class RequestOptions
{
    private float $_timeout = 0;
    private float $_connectTimeout = 0;
    private bool $_form = false;
    private bool $_json = false;
    private ?string $_authorization = null;

    public function __construct(?array $options = [])
    {
        if (isset($options['timeout'])) {
            $this->_timeout = (float)$options['timeout'];
        }
        if (isset($options['connect_timeout'])) {
            $this->_connectTimeout = (float)$options['connect_timeout'];
        }
        if (isset($options['form'])) {
            $this->_form = (bool)$options['form'];
        }
        if (isset($options['json'])) {
            $this->_json = (bool)$options['json'];
        }
        if (isset($options['authorization'])) {
            $authorization = \strpos($options['authorization'], 'Bearer ') === 0
                ? $options['authorization']
                : 'Bearer ' . $options['authorization'];
            $this->_authorization = $authorization;
        }
    }

    public function getTimeout(): float
    {
        return $this->_timeout;
    }
    public function getConnectTimeout(): float
    {
        return $this->_connectTimeout;
    }
    public function isForm(): bool
    {
        return $this->_form;
    }
    public function isJson(): bool
    {
        return $this->_json;
    }
    public function getAuthorization(): ?string
    {
        return $this->_authorization;
    }
}
