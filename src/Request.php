<?php

namespace Bredala\Http;

/**
 * Request
 *
 * Helps to get data from super globales
 */
class Request
{
    private array $uri = [];
    private ?array $queryParams = null;
    private ?array $formParams = null;
    private ?array $jsonParams = null;
    private ?array $uploadedFiles = null;

    // -------------------------------------------------------------------------
    // Initialize
    // -------------------------------------------------------------------------

    /**
     * @param array $config
     */
    public function __construct()
    {
        if (!isset($_SERVER['REQUEST_TIME'])) {
            $_SERVER['REQUEST_TIME'] = time();
        }

        if (!isset($_SERVER['REQUEST_TIME_FLOAT'])) {
            $_SERVER['REQUEST_TIME_FLOAT'] = microtime(true);
        }

        $this->uri = $this->parseUri();
    }

    // -------------------------------------------------------------------------
    // Server info
    // -------------------------------------------------------------------------

    /**
     * Returns an array of server params
     *
     * @return array
     */
    public function serverParams(): array
    {
        return $_SERVER;
    }

    /**
     * Returns a server param or a default value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function server(string $key, $default = null)
    {
        return $_SERVER[$key] ?? $default;
    }

    /**
     * Returns the request time in seconds
     *
     * @return integer
     */
    public function time(): int
    {
        return $this->server('REQUEST_TIME');
    }

    /**
     * Returns the request time in seconds with more precision
     *
     * @return float
     */
    public function mtime(): float
    {
        return $this->server('REQUEST_TIME_FLOAT');
    }

    // -------------------------------------------------------------------------
    // Request method
    // -------------------------------------------------------------------------

    /**
     * Returns HTTP request's method
     *
     * @return string
     */
    public function method(): string
    {
        return $this->server('REQUEST_METHOD', 'CLI');
    }

    /**
     * Returns HTTP request's is using GET method
     *
     * @return bool
     */
    public function isGet(): bool
    {
        return $this->method() === 'GET';
    }

    /**
     * Returns HTTP request's is using POST method
     *
     * @return bool
     */
    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    /**
     * Returns HTTP request's is using PUT method
     *
     * @return bool
     */
    public function isPut(): bool
    {
        return $this->method() === 'PUT';
    }

    /**
     * Returns HTTP request's is using PATCH method
     *
     * @return bool
     */
    public function isPatch(): bool
    {
        return $this->method() === 'PATCH';
    }

    /**
     * Returns HTTP request's is using DELETE method
     *
     * @return bool
     */
    public function isDelete(): bool
    {
        return $this->method() === 'DELETE';
    }

    /**
     * Returns if the request is using the command line
     *
     * @return bool
     */
    public function isClient(): bool
    {
        return $this->method() === 'CLI';
    }

    /**
     * Returns if the current request is an ajax request
     *
     * @return bool
     */
    public function isAjax(): bool
    {
        return $this->server('HTTP_X_REQUESTED_WITH') === 'XMLHttpRequest';
    }

    /**
     * Returns if the request is using the HTTPS protocol
     *
     * @return bool
     */
    public function isSecure(): bool
    {
        $https = $this->server('HTTPS', '');
        return !empty($https) && $https !== 'off';
    }

    /**
     * Returns the request URI
     *
     * @return string
     */
    public function uri(): string
    {
        return $this->uri['path'] ?? '/';
    }

    // -------------------------------------------------------------------------
    // Request Data
    // -------------------------------------------------------------------------

    /**
     * Returns an array of query params
     *
     * @return array
     */
    public function queryParams(): array
    {
        if ($this->queryParams === null) {
            $this->queryParams = $this->parseQueryParams();
        }

        return $this->queryParams;
    }

    /**
     * Returns a query param or a default value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function queryParam(string $key, $default = null)
    {
        $params = $this->queryParams();
        return $params[$key] ?? $default;
    }

    /**
     * Returns an array of form params
     *
     * @return array
     */
    public function formParams(): array
    {
        if ($this->formParams === null) {
            $this->formParams = $this->parseFormParams();
        }

        return $this->formParams;
    }

    /**
     * Returns a form param or a default value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function formParam(string $key, $default = null)
    {
        $params = $this->formParams();
        return $params[$key] ?? $default;
    }

    /**
     * Returns an array of json params
     *
     * @return array
     */
    public function jsonParams(): array
    {
        if ($this->jsonParams === null) {
            $this->jsonParams = $this->parseJsonParams();
        }

        return $this->jsonParams;
    }

    /**
     * Returns a json param or a default value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function jsonParam(string $key, $default = null)
    {
        $params = $this->jsonParams();
        return $params[$key] ?? $default;
    }

    /**
     * Returns an array of uploaded files
     *
     * @return array
     */
    public function uploadedFiles(): array
    {
        if ($this->uploadedFiles === null) {
            $this->uploadedFiles = $this->parseUploadedFiles();
        }

        return $this->uploadedFiles;
    }

    /**
     * Returns an uploaded file
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function uploadedFile(string $key)
    {
        $files = $this->uploadedFiles();
        return $files[$key] ?? null;
    }

    // -------------------------------------------------------------------------
    // Client
    // -------------------------------------------------------------------------

    /**
     * Returns the user agent
     *
     * @return string
     */
    public function userAgent(): string
    {
        return $this->server('HTTP_USER_AGENT', '');
    }

    /**
     * Returns the IP Address
     *
     * @return string
     */
    public function ip(): string
    {
        if ($this->ip === null) {
            $this->ip = $this->parseIp();
        }

        return $this->ip;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    protected function parseUri(): array
    {
        $uri = $_SERVER['REQUEST_URI'] ?? $this->parseArgv();
        $uri = preg_replace('#//+#', '/', $uri);
        $uri = '/' . trim($uri, '/');

        return parse_url('http://dummy' . $uri);
    }

    protected function parseArgv(): string
    {
        if ($this->server('argc', 0) < 2) {
            return '/';
        }

        $argv = $this->server('argv');
        array_shift($argv);

        return implode('/', $argv);
    }

    protected function parseQueryParams()
    {
        if ($_GET) {
            return $_GET;
        }

        if (($query = $this->uri['query'] ?? null)) {
            return self::parseQueryString($query);
        }

        return [];
    }

    protected function parseFormParams(): array
    {
        if ($_POST) {
            return $_POST;
        }

        // PUT, PATCH ...
        if ($this->isPut() || $this->isPatch()) {
            if (($input = file_get_contents('php://input'))) {
                return self::parseQueryString($input);
            }
        }

        return [];
    }

    protected function parseJsonParams(): array
    {
        if ($this->isPost() || $this->isPut() || $this->isPatch()) {
            if (($content = file_get_contents('php://input'))) {
                $data = json_decode($content, true);
                if (JSON_ERROR_NONE === json_last_error() && is_array($data)) {
                    return $data;
                }
            }
        }

        return [];
    }

    private static function parseQueryString(string $query): array
    {
        $data = [];
        return mb_parse_str($query, $data) ? $data : [];
    }

    protected function parseIp(): string
    {
        $ip = filter_var($this->server('REMOTE_ADDR', ''), FILTER_VALIDATE_IP);
        return $ip ?: '0.0.0.0';
    }

    /**
     * Return an UploadedFile instance array.
     *
     * @param array $files A array which respect $_FILES structure
     *
     * @throws InvalidArgumentException for unrecognized values
     */
    protected function parseUploadedFiles(): array
    {
        $files = [];

        foreach ($_FILES as $key => $value) {
            if (is_array($value['tmp_name'])) {
                foreach ($value as $ppt => $arr) {
                    foreach ($arr as $i => $v) {
                        $files[$key][$i][$ppt] = $v;
                    }
                }
            } else {
                $files[$key] = $value;
            }
        }

        return $files;
    }

    // -------------------------------------------------------------------------
}
