# bredala-http — API cheat sheet

Quick lookup by intent. This is not exhaustive — read the source in `vendor/sugatasei/bredala-http/src/` for exact signatures/edge cases not covered here.

## Request (`Bredala\Http\Request`)

Build with `Request::createFromServer()` (reads superglobals) or `Request::create()` + manual setters (useful in tests).

| Intent                                         | Method                                                                    |
| ---------------------------------------------- | ------------------------------------------------------------------------- |
| Current path (no query string)                 | `uri(): string`                                                           |
| Scheme + host, e.g. `https://example.com`      | `baseUrl(): string`                                                       |
| Full current URL, optionally with query string | `currentUrl(bool $withQueryString = true): string`                        |
| Raw `$_SERVER` array / single value            | `servers(): array` / `server(string $name): mixed`                        |
| Request timestamp (int / float with µs)        | `time(): int` / `mtime(): float`                                          |
| HTTP method, `'CLI'` if none                   | `method(): string`                                                        |
| Method checks                                  | `isGet()`, `isPost()`, `isPut()`, `isPatch()`, `isDelete()`, `isClient()` |
| `X-Requested-With: XMLHttpRequest` check       | `isAjax(): bool`                                                          |
| HTTPS check (see gotchas re: proxies)          | `isSecure(): bool`                                                        |
| `User-Agent` header                            | `userAgent(): ?string`                                                    |
| Client IP, validated, `'0.0.0.0'` if unknown   | `ip(): string`                                                            |
| Query params (array / single)                  | `queryParams(): array` / `queryParam(string $name): mixed`                |
| Body params — form or JSON, array / single     | `bodyParams(): array` / `bodyParam(string $name): mixed`                  |
| Uploaded files — all / one field               | `attachements(): array` / `attachement(string $name): ?array`             |
| Cookies — all / one                            | `cookies(): array` / `cookie(string $name): mixed`                        |

## Response (`Bredala\Http\Response`)

Build with `Response::createFromServer()` (picks up protocol version, HTTPS, and CORS request headers) or `Response::create()`.

**Config** (call before building the response body, all fluent):
`setStreamFactory()`, `setBuffer(int)`, `setCookieDomain()`, `setCookiePath()`, `setCookieSecure()`, `setCookieHttponly()`, `setCookieSamesite()`, `setCorsOrigin()`, `setCorsMethods()`, `setCorsHeaders()`.

**Status / protocol:**
`getProtocolVersion()` / `setProtocolVersion(string $version)` (accepts `"1.1"` or `"HTTP/1.1"`), `getStatusCode()` / `setStatusCode(int $code, ?string $reason = null)`.

**Headers:**
`getHeaders(): array`, `getHeader(string $name): string[]`, `hasHeader(string $name): bool`, `addHeader(string $name, string $value, bool $replace = false)`, `removeHeader(string $name)`, `setContentType(string $mime, string $charset = "UTF-8")` (accepts a mime-map key like `'json'` or a literal mime string).

**Cookies:**
`addCookie(string $name, $value, int $expire = 0, $settings = [])`, `removeCookie(string $name, $settings = [])` (sets expiry in the past).

**Redirect / cache:**
`redirect(string $url = "/", bool $temporary = true)` (302 or 301; resets headers/body but keeps cookies), `cache(int $age = 86400)`, `noCache()`.

**CORS:**
`cors(?string $origin = null, ?string $methods = null, ?string $headers = null)` — writes the `Access-Control-Allow-*` headers using the config set via `setCorsOrigin()`/etc. or `createFromServer()`, unless overridden by the arguments.

**Body:**
`getBody(): StreamInterface`, `setBody($body = "")` (accepts `StreamInterface`, resource, scalar, `Stringable`, or `null`), `setBodyFile(string $filename, string $mode = 'r')` (streamed, not loaded in memory), `setText(string $data = '')`, `setJson(mixed $data = null, int $flags = 0)` (throws `\JsonException` on failure), `setJsonException(ResponseException $ex)`.

**Rendering (call exactly once, at the end):**
`emit(?int $bufferLength = null)`, `emitHeaders()`, `emitBody(?int $bufferLength = null)`.

**Misc:**
`reset()` — clears headers + body, keeps config (stream factory, buffer, cookie/CORS settings).

Full IANA status code list: `Response::HTTP_*` constants, reason phrases in `HttpStatusTrait::$statusReasons`. Full mime-type key map: `MimesTypesTrait::$mimesTypes` (hundreds of entries — grep it rather than guessing a key).

## Session (`Bredala\Http\Session`)

`__construct(?SessionHandlerInterface $handler = null)` — pass a handler for custom storage (Redis, DB, etc.), otherwise uses PHP's default.

| Intent                                                               | Method                                                                                 |
| -------------------------------------------------------------------- | -------------------------------------------------------------------------------------- |
| Start/resume session, expire flash & temp data                       | `start(?string $id = null): static`                                                    |
| Current session ID (null if not started)                             | `id(): ?string`                                                                        |
| All session data                                                     | `all(): array`                                                                         |
| Has / get / set a value                                              | `has(string $name)`, `get(string $name, $default = null)`, `set(string $name, $value)` |
| Set a value that survives one more request                           | `setFlash(string $name, $value)`                                                       |
| Set a value that expires after N seconds                             | `setTemp(string $name, $value, int $time = 300)`                                       |
| Mark/unmark existing keys as flash/temp without re-setting the value | `markFlash()`, `unmarkFlash()`, `markTemp()`, `unmarkTemp()`                           |
| Delete one key                                                       | `delete(string $name)`                                                                 |
| Clear all session vars (keeps the session open)                      | `reset()`                                                                              |
| Destroy the session entirely                                         | `destroy()`                                                                            |
| Write and close (before a long-running response)                     | `close()`                                                                              |

## Exceptions (`Bredala\Http\ResponseException`)

One exception covers every status: `new ResponseException(int $status, string $message = 'default', ?Throwable $previous = null)`. It extends `\Exception` and implements `JsonSerializable`; the status is the exception code, readable via `getCode()`. Pass a `Response::HTTP_*` constant rather than a literal.

| Status                                   | Typical use                            |
| ---------------------------------------- | -------------------------------------- |
| `Response::HTTP_BAD_REQUEST` (400)       | Invalid input / validation failure     |
| `Response::HTTP_UNAUTHORIZED` (401)      | Missing/invalid credentials            |
| `Response::HTTP_FORBIDDEN` (403)         | Authenticated but not allowed          |
| `Response::HTTP_NOT_FOUND` (404)         | Resource doesn't exist                 |
| `Response::HTTP_NOT_ACCEPTABLE` (406)    | Can't satisfy requested representation |
| `Response::HTTP_GONE` (410)              | Expired token/session/CSRF             |
| `Response::HTTP_UNPROCESSABLE_ENTITY` (422) | Well-formed but semantically invalid |
| `Response::HTTP_LOCKED` (423)            | Resource locked (e.g. concurrent edit) |
| `Response::HTTP_INTERNAL_SERVER_ERROR` (500) | Unexpected failure                 |

`ResponseException` API:
- `setErrors(array)`, `addError(string $key, $value)`, `getErrors()` — field-level validation details
- `setExtra(array)`, `addExtra(string $key, $value)`, `getExtra()` — any other payload data
- `jsonSerialize()` → `['status' => ..., 'error' => ..., 'errors' => ..., 'extra' => ...]` — `errors`/`extra` serialize to `[]` when empty, never to an object or `null`
