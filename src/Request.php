<?php

namespace Bredala\Http;

/**
 * Request
 *
 * Helps to get data from super globales
 */
class Request
{
    private string $uri = '/';
    private array $servers = [];
    private array $queryParams = [];
    private array $bodyParams = [];
    private array $attachements = [];
    private array $cookies = [];
    private ?string $ip = null;

    // -------------------------------------------------------------------------
    // Constructors
    // -------------------------------------------------------------------------

    public static function create(): static
    {
        return new static();
    }

    public static function createFromServer(): static
    {
        $uri = self::parseUri();

        return static::create()
            ->setUri($uri['path'] ?? '/')
            ->setServers($_SERVER)
            ->setQueryParams(self::parseQueryParams($uri['query'] ?? null))
            ->setBodyParams(self::parseBodyParams())
            ->setAttachements(self::parseAttachements())
            ->setCookies($_COOKIE);
    }

    // -------------------------------------------------------------------------
    // Setters
    // -------------------------------------------------------------------------

    /**
     * Sets current uri
     *
     * @param string $uri
     * @return static
     */
    public function setUri(string $uri): static
    {
        $this->uri = $uri;
        return $this;
    }

    /**
     * Sets server params
     *
     * @param array $values
     * @return static
     */
    public function setServers(array $values): static
    {
        $this->servers = $values;
        return $this;
    }

    /**
     * Sets query params
     *
     * @param array $values
     * @return static
     */
    public function setQueryParams(array $values): static
    {
        $this->queryParams = $values;
        return $this;
    }

    /**
     * Sets body params
     *
     * @param array $values
     * @return static
     */
    public function setBodyParams(array $values): static
    {
        $this->bodyParams = $values;
        return $this;
    }

    /**
     * Set Attachements
     *
     * @param array $values
     * @return static
     */
    public function setAttachements(array $values): static
    {
        $this->attachements = $values;

        if (!isset($this->attachements['REQUEST_TIME'])) {
            $this->attachements['REQUEST_TIME'] = time();
        }

        if (!isset($this->attachements['REQUEST_TIME_FLOAT'])) {
            $this->attachements['REQUEST_TIME_FLOAT'] = microtime(true);
        }

        return $this;
    }

    /**
     * Sets cookies
     *
     * @param array $values
     * @return static
     */
    public function setCookies(array $values): static
    {
        $this->cookies = $values;
        return $this;
    }

    // -------------------------------------------------------------------------
    // Getters
    // -------------------------------------------------------------------------

    /**
     * Returns current uri
     *
     * @return string
     */
    public function uri(): string
    {
        return $this->uri;
    }

    /**
     * Returns server params
     *
     * @return array
     */
    public function servers(): array
    {
        return $this->servers;
    }

    /**
     * Returns a server param
     *
     * @param string $name
     * @return mixed
     */
    public function server(string $name): mixed
    {
        return $this->servers[$name] ?? null;
    }

    /**
     * Returns the request time in seconds
     *
     * @return integer
     */
    public function time(): int
    {
        return $this->server('REQUEST_TIME') ?? 0;
    }

    /**
     * Returns the request time in seconds with more precision
     *
     * @return float
     */
    public function mtime(): float
    {
        return $this->server('REQUEST_TIME_FLOAT') ?? 0;
    }

    /**
     * Returns HTTP request's method
     *
     * @return string
     */
    public function method(): string
    {
        return $this->server('REQUEST_METHOD') ?? 'CLI';
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
     * Returns the user agent
     *
     * @return string
     */
    public function userAgent(): ?string
    {
        return $this->server('HTTP_USER_AGENT');
    }

    /**
     * Returns the IP Address
     *
     * @return string
     */
    public function ip(): string
    {
        if ($this->ip === null) {
            $ip = filter_var($this->server('REMOTE_ADDR', ''), FILTER_VALIDATE_IP);
            $this->ip = $ip ?: '0.0.0.0';
        }

        return $this->ip;
    }

    // -------------------------------------------------------------------------

    /**
     * Gets query params
     *
     * @return array
     */
    public function queryParams(): array
    {
        return $this->queryParams;
    }

    /**
     * Gets query param
     *
     * @param string $name
     * @return mixed
     */
    public function queryParam(string $name): mixed
    {
        return $this->queryParam[$name] ?? null;
    }

    /**
     * Gets body params
     *
     * @return array
     */
    public function bodyParams(): array
    {
        return $this->bodyParams;
    }

    /**
     * Gets body param
     *
     * @param string $name
     * @return mixed
     */
    public function bodyParam(string $name): mixed
    {
        return $this->bodyParams[$name];
    }

    /**
     * Gets attachements
     *
     * @return array
     */
    public function attachements(): array
    {
        return $this->attachements;
    }

    /**
     * Gets attachement
     *
     * @param string $name
     * @return array
     */
    public function attachement(string $name): ?array
    {
        return $this->attachements[$name] ?? null;
    }

    // -------------------------------------------------------------------------

    /**
     * Gets cookies
     *
     * @return array
     */
    public function cookies(): array
    {
        return $this->cookies;
    }

    /**
     * Get cookie
     *
     * @param string $name
     * @return mixed
     */
    public function cookie(string $name): mixed
    {
        return $this->cookies[$name] ?? null;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Parse Uri
     *
     * @return array
     */
    private static function parseUri(): array
    {
        $uri = $_SERVER['REQUEST_URI'] ?? self::parseArgv();
        $uri = preg_replace('#//+#', '/', $uri);
        $uri = '/' . trim($uri, '/');

        return parse_url('http://dummy' . $uri);
    }

    /**
     * Parse CLI arguments
     *
     * @return string
     */
    private static function parseArgv(): string
    {
        if (($_SERVER['argc'] ?? 0) < 2) {
            return '/';
        }

        $argv = $_SERVER['argv'] ?? [];
        array_shift($argv);

        return implode('/', $argv);
    }

    /**
     * Parse query params
     *
     * @param string|null $queryString
     * @return array
     */
    private static function parseQueryParams(?string $queryString): array
    {
        if ($_GET) {
            return $_GET;
        }

        if ($queryString) {
            return self::parseQueryString($queryString);
        }

        return [];
    }

    /**
     * Parses a query string
     *
     * @param string $query
     * @return array
     */
    private static function parseQueryString(string $query): array
    {
        $data = [];
        return mb_parse_str($query, $data) ? $data : [];
    }

    /**
     * Parses body
     *
     * @return array
     */
    private static function parseBodyParams(): array
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? '';

        if ($method === 'PUT' || $method === 'POST') {
            if ($_POST) {
                return $_POST;
            } elseif (($input = file_get_contents('php://input'))) {
                $data = json_decode($input, true);
                if (is_array($data)) {
                    return $data;
                } else {
                    return self::parseQueryString($input);
                }
            }
        }

        return [];
    }

    /**
     * Returns an array of uploaded files indexed by field name.
     *
     * @return array
     */
    private static function parseAttachements(): array
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
