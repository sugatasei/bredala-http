# bredala-http

PHP object-oriented layer for the HTTP specification.

## Request

`Bredala\Http\Request` helps to get HTTP request informations.

### Server info

- `servers(): array` Returns an array of server params.
- `server(string $name)` Returns a server params.
- `time(): int` Returns the request time in seconds
- `mtime(): float` Returns the request time in seconds with more precision

### HTTP method

`method(): string` Request method (CLI, GET, POST, DELETE, ...).
`isGet(): bool` Returns HTTP request's is using GET method.
`isPost(): bool` Returns HTTP request's is using POST method.
`isPatch(): bool` Returns HTTP request's is using PATCH method.
`isDelete(): bool` Returns HTTP request's is using DELETE method.
`isClient(): bool` Returns HTTP request's is using GET method.
`isAjax(): bool` Returns if the current request is an ajax request.
`isSecure(): bool` Returns if the request is using the HTTPS protocol.
` uri(): string` Returns the request URI.

### HTTP Headers

- `cookies()` Returns an array of cookies.
- `cookie(string $name)` Returns a cookie value.

### Request data

- `queryParams(): array` Returns an array of query params.
- `queryParam(string $name)` Returns a query value.
- `bodyParams(): array` Returns an array of form/json params.
- `bodyParam(string $name)` Returns a form/json value.
- `attachements(): array` Returns an array of uploaded files indexed by field name.
- `attachement(string $name): array` Returns an uploaded files.

### Client

- `userAgent(): string` Returns the user agent.
- `ip(): string` Returns the IP Address.

## Response

`Bredala\Http\Response` helps to send HTTP response.

- `reset(): mixed` Reset the response.

### HTTP status

- `getProtocolVersion(): string` Returns HTTP protocole version.
- `setStatusCode(int $code, ?string $reason = null): static` Sets HTTP status. If reason is null, a default reason corresponding to the HTTP status code is set.

### HTTP headers

- `getHeaders(): array` Returns HTTP headers
- `setHeader(string $name, string $value): static` Sets HTTP header.
- `addHeader(string $name, string $value): static` Adds HTTP header. Userfull for headers with multiple values.
- `removeHeader(string $name): static` Removes HTTP header
- `setContentType(string $mime, string $charset = "UTF-8"): static` Sets content-type. File extension can be used for most of them.
    ````php
    $res->setContentType('jpg');
    $res->setContentType('image/jpeg');
    ````
- `addCookie(string $name, $value, int $expire = 0, $settings = []): static` Adds a cookie.
- `removeCookie(string $name): static` Removes cookie.
- `redirect(string $url = "/", bool $temporary = true): static` HTTP redirection.
- `cache(int $age = 86400): static` Sets HTTP cache.
- `noCache(): static` Forces HTTP no-cache.
- `cors(?string $origin = null, ?string $method = null): static` Enables CORS.

### Http body

`getBody(): Bredala\Http\Stream` Returns body.
`setBody(Bredala\Http\Stream|string $body = ""): static` Sets body.

### Rendering

`emitHeaders()` Sends headers.
`emitBody(int $bufferLength = 0)` Sends Content.
`emit(int $bufferLength = 0)` Sends headers & body.

## Session

`Bredala\Http\Session` helps to work with HTTP response.

- `__construct(\SessionHandlerInterface $handler = NULL)` The constructors can use an optionnal session handler.
- `start(): static` Start the session.
- `close(): static` Writes and closes current session.
- `destroy(): static` Destroys session.
- `reset(): static` Removes all sessions vars.
- `all()` Returns all session data.
- `has(string $name): bool` Returns all session data.
- `get(string $name, $default = null): mixed` Returns session data by name.
- `set(string $name, mixed $value): static` Sets session data by name.

### Flash data

Session data that will only be available for the next request, and is then automatically cleared. 

- `setFlash(string $name, $value): static` 
- `markFlash(string $name): static`
- `unmarkFlash(string $name): static`

### Temp data

Session data with a specific expiration time. After the value expires, or the session expires or is deleted, the value is automatically removed.

- `setTemp($name, $value, $time = 300)`
- `markTemp(string $name, int $time = 300)`
- `unmarkTemp(string $name)`
