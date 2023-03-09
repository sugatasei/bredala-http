<?php

namespace Bredala\Http;

class Response
{
    const HTTP_CONTINUE = 100;
    const HTTP_SWITCHING_PROTOCOLS = 101;
    const HTTP_PROCESSING = 102;                                      // RFC2518
    const HTTP_EARLY_HINTS = 103;                                     // RFC8297
    const HTTP_OK = 200;
    const HTTP_CREATED = 201;
    const HTTP_ACCEPTED = 202;
    const HTTP_NON_AUTHORITATIVE_INFORMATION = 203;
    const HTTP_NO_CONTENT = 204;
    const HTTP_RESET_CONTENT = 205;
    const HTTP_PARTIAL_CONTENT = 206;
    const HTTP_MULTI_STATUS = 207;                                    // RFC4918
    const HTTP_ALREADY_REPORTED = 208;                                // RFC5842
    const HTTP_IM_USED = 226;                                         // RFC3229
    const HTTP_MULTIPLE_CHOICES = 300;
    const HTTP_MOVED_PERMANENTLY = 301;
    const HTTP_FOUND = 302;
    const HTTP_SEE_OTHER = 303;
    const HTTP_NOT_MODIFIED = 304;
    const HTTP_USE_PROXY = 305;
    const HTTP_RESERVED = 306;
    const HTTP_TEMPORARY_REDIRECT = 307;
    const HTTP_PERMANENTLY_REDIRECT = 308;                            // RFC7238
    const HTTP_BAD_REQUEST = 400;
    const HTTP_UNAUTHORIZED = 401;
    const HTTP_PAYMENT_REQUIRED = 402;
    const HTTP_FORBIDDEN = 403;
    const HTTP_NOT_FOUND = 404;
    const HTTP_METHOD_NOT_ALLOWED = 405;
    const HTTP_NOT_ACCEPTABLE = 406;
    const HTTP_PROXY_AUTHENTICATION_REQUIRED = 407;
    const HTTP_REQUEST_TIMEOUT = 408;
    const HTTP_CONFLICT = 409;
    const HTTP_GONE = 410;
    const HTTP_LENGTH_REQUIRED = 411;
    const HTTP_PRECONDITION_FAILED = 412;
    const HTTP_REQUEST_ENTITY_TOO_LARGE = 413;
    const HTTP_REQUEST_URI_TOO_LONG = 414;
    const HTTP_UNSUPPORTED_MEDIA_TYPE = 415;
    const HTTP_REQUESTED_RANGE_NOT_SATISFIABLE = 416;
    const HTTP_EXPECTATION_FAILED = 417;
    const HTTP_I_AM_A_TEAPOT = 418;                                   // RFC2324
    const HTTP_MISDIRECTED_REQUEST = 421;                             // RFC7540
    const HTTP_UNPROCESSABLE_ENTITY = 422;                            // RFC4918
    const HTTP_LOCKED = 423;                                          // RFC4918
    const HTTP_FAILED_DEPENDENCY = 424;                               // RFC4918
    const HTTP_TOO_EARLY = 425;                                       // RFC-ietf-httpbis-replay-04
    const HTTP_UPGRADE_REQUIRED = 426;                                // RFC2817
    const HTTP_PRECONDITION_REQUIRED = 428;                           // RFC6585
    const HTTP_TOO_MANY_REQUESTS = 429;                               // RFC6585
    const HTTP_REQUEST_HEADER_FIELDS_TOO_LARGE = 431;                 // RFC6585
    const HTTP_UNAVAILABLE_FOR_LEGAL_REASONS = 451;
    const HTTP_INTERNAL_SERVER_ERROR = 500;
    const HTTP_NOT_IMPLEMENTED = 501;
    const HTTP_BAD_GATEWAY = 502;
    const HTTP_SERVICE_UNAVAILABLE = 503;
    const HTTP_GATEWAY_TIMEOUT = 504;
    const HTTP_VERSION_NOT_SUPPORTED = 505;
    const HTTP_VARIANT_ALSO_NEGOTIATES_EXPERIMENTAL = 506;            // RFC2295
    const HTTP_INSUFFICIENT_STORAGE = 507;                            // RFC4918
    const HTTP_LOOP_DETECTED = 508;                                   // RFC5842
    const HTTP_NOT_EXTENDED = 510;                                    // RFC2774
    const HTTP_NETWORK_AUTHENTICATION_REQUIRED = 511;                 // RFC6585

    use HttpStatusTrait;
    use MimesTypesTrait;

    protected Request $request;
    protected array $settings = [];

    protected string $version;
    protected int $statusCode;
    protected string $statusReason;

    protected array $headers;

    protected ?Stream $body;

    public function __construct(Request $request, array $settings = [])
    {
        $this->request = $request;
        $this->settings = $settings + [
            "buffer"   => 0,
            "prefix"   => "",
            "domain"   => "",
            "path"     => "/",
            "secure"   => $this->isSecure(),
            "httponly" => true,
            "samesite" => '',
        ];

        $this->reset();
    }

    public function reset()
    {
        $this->headers = [];
        $this->body = null;

        $this->setProtocolVersion($this->findProtocolVersion());
        $this->setStatusCode(200);
    }

    private function isSecure(): bool
    {
        $https = $this->request->server("HTTPS") ?? "";
        return !empty($https) && $https !== "off";
    }

    private function findProtocolVersion()
    {
        return $this->request->server("SERVER_PROTOCOL") ?? "1.0";
    }

    // -------------------------------------------------------------------------
    // Status
    // -------------------------------------------------------------------------

    /**
     * Returns HTTP protocole version
     *
     * @return string
     */
    public function getProtocolVersion(): string
    {
        return $this->version;
    }

    /**
     * Sets HTTP protocole version
     *
     * @param string $version
     * @return $this
     */
    public function setProtocolVersion(string $version): static
    {
        if (mb_strpos($version, "HTTP/") === 0) {
            $version = mb_substr($version, 5);
        }

        $this->version = $version;

        return $this;
    }

    /**
     * Returns HTTP status
     *
     * @return integer
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Sets HTTP status
     *
     * @param integer $code
     * @param string|null $reason
     * @return $this
     */
    public function setStatusCode(int $code, ?string $reason = null): static
    {
        $this->statusCode = $code;
        $this->statusReason = $reason ?? self::$statusReasons[$code] ?? "Unknown Status";

        return $this;
    }

    // -------------------------------------------------------------------------
    // Headers
    // -------------------------------------------------------------------------

    /**
     * Returns HTTP headers
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Sets HTTP header
     *
     * @param string $value
     * @param bool $replace
     * @return $this
     */
    public function setHeader(string $name, string $value): static
    {
        $name = self::normalizeHeaderName($name);
        $this->headers[$name] = [$value];

        return $this;
    }

    /**
     * Adds HTTP header
     *
     * @param string $name
     * @param string $value
     * @return static
     */
    public function addHeader(string $name, string $value): static
    {
        $name = self::normalizeHeaderName($name);

        if (!isset($this->headers[$name])) {
            $this->headers[$name] = [];
        }

        $this->headers[$name][] = $value;

        return $this;
    }

    /**
     * Removes a header
     *
     * @param string $name
     * @return static
     */
    public function removeHeader(string $name): static
    {
        $name = self::normalizeHeaderName($name);

        if (isset($this->headers[$name])) {
            unset($this->headers[$name]);
        }

        return $this;
    }

    /**
     * Sets content-type
     *
     * @param string $mime
     * @return $this
     */
    public function setContentType(string $mime, string $charset = "UTF-8"): static
    {
        $mime = self::$mimesTypes[$mime][0] ?? $mime;

        if ($charset) {
            $this->setHeader("content-type", "{$mime}; charset={$charset}");
        } else {
            $this->setHeader("content-type", "{$mime}");
        }

        return $this;
    }

    /**
     * Creates a new cookie
     *
     * @param string $name
     * @param mixed $value
     * @param integer $expire
     * @return $this
     */
    public function addCookie(string $name, $value, int $expire = 0, $settings = []): static
    {
        $settings = $settings + $this->settings;

        $header = $name . '=' . urlencode($value);

        if (($domain = $settings['domain'] ?? null)) {
            $header .= '; domain=' . $domain;
        }

        if (($path = $settings['path'] ?? null)) {
            $header .= '; path=' . $path;
        }

        if ($expire) {
            $header .= '; expires=' . self::gmdate($expire);
        }

        if (($settings['secure'] ?? null)) {
            $header .= '; secure';
        }

        if (($settings['httponly'] ?? null)) {
            $header .= '; HttpOnly';
        }

        $samesite = $settings['samesite'] ?? null;
        if ($samesite && in_array(strtolower($samesite), ['lax', 'strict'], true)) {
            $header .= '; SameSite=' . $samesite;
        }

        return $this->addHeader('Set-cookie', $header);
    }

    /**
     * Removes a cookie
     *
     * @param string $name
     * @return $this
     */
    public function removeCookie(string $name): static
    {
        return $this->addCookie($name, "", strtotime("-1 day"));
    }

    /**
     * HTTP Redirect
     *
     * @param string $url
     * @param boolean $temporary
     * @return $this
     */
    public function redirect(string $url = "/", bool $temporary = true): static
    {
        $this->reset();
        $this->setStatusCode($temporary ? 302 : 301);
        $this->setHeader("location", filter_var($url, FILTER_SANITIZE_URL));

        return $this;
    }

    /**
     * Sets HTTP cache
     *
     * @param integer $age
     * @return $this
     */
    public function cache(int $age = 86400): static
    {
        $this->setHeader("pragma", "public");
        $this->setHeader("cache-control", "max-age=" . $age);
        $this->setHeader("expires", self::gmdate(time() + $age));

        return $this;
    }

    /**
     * Force HTTP no cache
     *
     * @return $this
     */
    public function noCache(): static
    {
        $this->setHeader("expires", "Mon, 26 Jul 1990 05:00:00 GMT");
        $this->setHeader("last-modified", "" . gmdate("D, d M Y H:i:s") . " GMT");
        $this->setHeader("cache-control", "no-store, no-cache, must-revalidate");
        $this->setHeader("cache-control", "post-check=0, pre-check=0", false);
        $this->setHeader("pragma", "no-cache");

        return $this;
    }

    /**
     * Enables CORS
     *
     * @param string|null $origin
     * @param string|null $method
     * @return $this
     */
    public function cors(?string $origin = null, ?string $method = null): static
    {
        $origin = $origin ?? $this->request->server("HTTP_ORIGIN") ?? "*";
        $method = $method ?? $this->request->server("HTTP_ACCESS_CONTROL_REQUEST_METHOD") ?? "GET, POST, PUT, PATCH, DELETE, OPTIONS";
        $headers = $this->request->server("HTTP_ACCESS_CONTROL_REQUEST_HEADERS") ?? "*";

        $this->setHeader("access-control-allow-origin", $origin);
        $this->setHeader("access-control-allow-credentials", "true");
        $this->setHeader("access-control-max-age", "86400");
        $this->setHeader("access-control-allow-methods", $method);
        $this->setHeader("access-control-allow-headers", $headers);

        return $this;
    }

    // -------------------------------------------------------------------------
    // Body
    // -------------------------------------------------------------------------

    /**
     * Returns body
     *
     * @return Stream
     */
    public function getBody(): Stream
    {
        if ($this->body === null) {
            $this->body = new Stream("");
        }

        return $this->body;
    }

    /**
     * Sets body
     *
     * @param mixed $body
     * @return object
     */
    public function setBody($body = ""): static
    {
        if ($body instanceof Stream) {
            $this->body = $body;
        } else {
            $this->body = new Stream($body);
        }

        return $this;
    }

    // -------------------------------------------------------------------------
    // Render
    // -------------------------------------------------------------------------

    /**
     * Sends headers & body
     *
     * @return void
     */
    public function emit(int $bufferLength = 0)
    {
        $this->emitHeaders();
        $this->emitBody($bufferLength);
    }

    /**
     * Sends headers
     *
     * @return $this
     */
    public function emitHeaders()
    {
        // Headers have already been sent by the developer
        if (headers_sent()) {
            return;
        }

        // Status line
        header("HTTP/{$this->version} {$this->statusCode} {$this->statusReason}", true, $this->statusCode);

        // Headers
        foreach ($this->headers as $name => $values) {
            $firstReplace = ($name === 'Set-Cookie') ? false : true;
            foreach ($values as $value) {
                header("{$name}: {$value}", $firstReplace);
                $firstReplace = false;
            }
        }
    }

    /**
     * Sends Content
     *
     * @return
     */
    public function emitBody(int $bufferLength = 0)
    {
        if ($bufferLength === null) {
            $bufferLength = $this->settings['buffer'];
        }

        if (!$bufferLength) {
            echo $this->getBody();
            return;
        }

        $body = $this->getBody();

        if ($body->isSeekable()) {
            $body->rewind();
        }

        while (!$body->eof()) {
            echo $body->read($bufferLength);
        }
    }

    // -------------------------------------------------------------------------

    private static function normalizeHeaderName(string $header): string
    {
        $header = str_replace('-', ' ', $header);
        $header = strtolower($header);
        $header = ucwords($header);
        $header = str_replace(' ', '-', $header);

        return $header;
    }

    private static function gmdate(int $date)
    {
        return gmdate("D, d M Y H:i:s T", $date);
    }

    // -------------------------------------------------------------------------
}
