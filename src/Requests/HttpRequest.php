<?php
declare(strict_types = 1);

namespace Transport\Requsts;

class HttpRequest
{
    public const METHOD_GET = 'GET';
    public const METHOD_POST = 'POST';
    public const METHOD_PUT = 'PUT';
    public const METHOD_DELETE = 'DELETE';

    public const METHODS = [
        self::METHOD_GET,
        self::METHOD_POST,
        self::METHOD_PUT,
        self::METHOD_DELETE,
    ];

    public const CONFIG_TIMEOUT = 'timeout';
    public const CONFIG_SSL_VERIFY_PEER = 'sslVerifyPeer';

    public const CONFIGS = [
        self::CONFIG_TIMEOUT => 4,
        self::CONFIG_SSL_VERIFY_PEER => false,
    ];

    protected string $method = self::METHOD_GET;
    protected string $url;
    protected array $data;
    protected array $files;
    protected array $file;
    protected array $headers;
    protected array $configs;

    /**
     * @return array|null
     */
    public function getData(): ?array
    {
        return $this->data;
    }

    /**
     * @return array|null
     */
    public function getHeaders(): ?array
    {
        return $this->headers;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * @return array
     */
    public function getFiles(): array
    {
        return $this->files ?? [];
    }

    /**
     * @return array
     */
    public function getFile(): array
    {
        return $this->file ?? [];
    }

    /**
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->configs[self::CONFIG_TIMEOUT] ?? self::CONFIGS[self::CONFIG_TIMEOUT];
    }

    /**
     * @return bool
     */
    public function isSslVerifyPeer(): bool
    {
        return $this->configs[self::CONFIG_SSL_VERIFY_PEER] ?? self::CONFIGS[self::CONFIG_SSL_VERIFY_PEER];
    }

    /**
     * @param array $data
     * @return self
     */
    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @param array $headers
     * @return self
     */
    public function setHeaders(array $headers): self
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * @param string $method
     * @return $this
     * @throws \HttpRequestMethodException
     */
    public function setMethod(string $method): self
    {
        if (!in_array($method, self::METHODS)) {
            throw new \HttpRequestMethodException('Method not fount');
        }

        $this->method = $method;

        return $this;
    }

    /**
     * @param string $url
     * @return self
     */
    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @param string $path
     * @param string $fieldsName
     * @return self
     * @throws \Exception
     */
    public function addFile(string $path, string $fieldsName = 'files'): self
    {
        if (!file_exists($path)) {
            throw new \Exception('File not fount');
        }

        $this->files[$fieldsName][] = $path;

        return $this;
    }

    /**
     * @param string $path
     * @param string $fieldName
     * @return self
     * @throws \Exception
     */
    public function putFile(string $path, string $fieldName = 'file'): self
    {
        if (!file_exists($path)) {
            throw new \Exception('File not fount');
        }

        $this->file[$fieldName] = $path;

        return $this;
    }

    /**
     * @param int $timeout
     * @return self
     */
    public function setTimeout(int $timeout): self
    {
        $this->configs[self::CONFIG_TIMEOUT] = $timeout;

        return $this;
    }

    /**
     * @param bool $sslVerifyPeer
     * @return self
     */
    public function setSslVerifyPeer(bool $sslVerifyPeer): self
    {
        $this->configs[self::CONFIG_SSL_VERIFY_PEER] = $sslVerifyPeer;

        return $this;
    }
}