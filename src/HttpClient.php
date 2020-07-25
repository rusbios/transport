<?php
declare(strict_types = 1);

namespace Transport;

use Exception;
use Transport\Requsts\HttpRequest;
use Transport\Responses\HttpResponse;

class HttpClient
{
    protected array $configs;

    /**
     * @param array $configs
     * @return $this
     * @throws \HttpInvalidParamException
     */
    public function setConfigs(array $configs): self
    {
        foreach ($configs as $name => $value) {
            $this->setConfig($name, $value);
        }

        return $this;
    }

    /**
     * @param string $name
     * @param string|int|bool|float $value
     * @return $this
     * @throws \HttpInvalidParamException
     */
    public function setConfig(string $name, $value): self
    {
        if (empty(HttpRequest::CONFIGS[$name])) {
            throw new \HttpInvalidParamException('Config not fount');
        }

        $this->configs[$name] = $value;

        return $this;
    }

    /**
     * @param string $name
     * @return string|int|bool|float
     * @throws \HttpInvalidParamException
     */
    public function getConfig(string $name)
    {
        if (!isset(self::CONFIGS[$name])) {
            throw new \HttpInvalidParamException('Config not fount');
        }

        return $this->configs[$name] ?? HttpRequest::CONFIGS[$name];
    }

    /**
     * @param HttpRequest $request
     * @return HttpResponse
     * @throws \HttpRequestException
     */
    public function get(HttpRequest $request): HttpResponse
    {
        $request->setMethod(HttpRequest::METHOD_GET);
        return $this->make($request);
    }

    /**
     * @param HttpRequest $request
     * @return HttpResponse
     * @throws \HttpRequestException
     */
    public function post(HttpRequest $request): HttpResponse
    {
        $request->setMethod(HttpRequest::METHOD_POST);
        return $this->make($request);
    }

    /**
     * @param HttpRequest $request
     * @return HttpResponse
     * @throws \HttpRequestException
     */
    public function put(HttpRequest $request): HttpResponse
    {
        $request->setMethod(HttpRequest::METHOD_PUT);
        return $this->make($request);
    }

    /**
     * @param HttpRequest $request
     * @return HttpResponse
     * @throws \HttpRequestException
     */
    public function delete(HttpRequest $request): HttpResponse
    {
        $request->setMethod(HttpRequest::METHOD_DELETE);
        return $this->make($request);
    }

    /**
     * @param HttpRequest $request
     * @return HttpResponse
     * @throws \HttpRequestException
     */
    public function make(HttpRequest $request): HttpResponse
    {
        $data = $request->getData();

        foreach ($this->configs as $name => $value) {
            switch ($name) {
                case HttpRequest::CONFIG_TIMEOUT:
                    $request->setTimeout($value);
                    break;

                case HttpRequest::CONFIG_SSL_VERIFY_PEER:
                    $request->setSslVerifyPeer($value);
                    break;
            }
        }

        $curl = curl_init($url);

        switch ($request->getMethod()) {
            case HttpRequest::METHOD_POST:
                curl_setopt($curl, CURLOPT_POST, true);
                break;

            case HttpRequest::METHOD_PUT:
                curl_setopt($curl, CURLOPT_PUT, true);
                break;

            case HttpRequest::METHOD_DELETE:
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, self::METHOD_DELETE);
                break;

            case HttpRequest::METHOD_GET:
                $data = http_build_query($request->getData(), '', '&');
                break;
        }

        if ($request->getMethod() != HttpRequest::METHOD_GET) {
            foreach ($request->getFiles() as $key => $path) {
                $data[$key][] = curl_file_create($path);
            }
            foreach ($request->getFile() as $key => $path) {
                $data[$key] = curl_file_create($path);
            }
        }
        if ($data) curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        if ($request->getHeaders()) {
            $headers = '';
            foreach ($request->getHeaders() as $key => $value) {
                $headers .= strtoupper($key) . ': ' . $value . '\r\n';
            }

            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $headers);
        }

        curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($curl, CURLOPT_TIMEOUT, $request->getTimeout());
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, $request->isSslVerifyPeer());

        $out = curl_exec($curl);

        if ($out === false) {
            throw new \HttpRequestException('cURL Error: ' . curl_error($curl));
        }

        $response = new HttpResponse(
            curl_getinfo($curl, CURLINFO_HTTP_CODE),
            curl_getinfo($curl, CURLINFO_CONTENT_TYPE),
            $out
        );

        curl_close($curl);

        return $response;
    }
}