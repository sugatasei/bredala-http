# Bredala/Http

Couche d'abstraction pour PHP pour analyser des requètes HTTP et créer des réponses HTTP.

Pas de routeur, pas de conteneur d'injection, pas de middleware : uniquement un lecteur de requète, un constructeur de réponse, une couche session et une exception porteuse de statut HTTP.

## Installation

```bash
composer require sugatasei/bredala-http
```

Le corps des réponses est un `Psr\Http\Message\StreamInterface`, construit via une factory PSR-17. Le package en exige une (`psr/http-factory-implementation`) sans en imposer une en particulier :

```bash
composer require nyholm/psr7
```

`Response` détecte automatiquement la première implémentation installée parmi `nyholm/psr7`, `guzzlehttp/psr7`, `laminas/laminas-diactoros`, `httpsoft/http-message` et `slim/psr7`. Si aucune n'est disponible, la première écriture dans le corps lève une `LogicException`. Pour choisir explicitement :

```php
$res = Response::create()->setStreamFactory(new Nyholm\Psr7\Factory\Psr17Factory());
```

## Claude Code

Ce package fournit un skill Claude Code dans [`skills/bredala-http/`](skills/bredala-http/) qui documente les patterns d'usage et les pièges de la librairie (redirect qui reset les headers, parsing JSON automatique, sessions, etc.).

Dans un projet qui dépend de `sugatasei/bredala-http`, copie-le une fois dans `.claude/skills/` après `composer install` pour que Claude Code le charge automatiquement (le nom du dossier doit correspondre au `name` déclaré dans `SKILL.md`) :

```bash
cp -r vendor/sugatasei/bredala-http/skills/bredala-http .claude/skills/bredala-http
```

## Request

`Bredala\Http\Request` Récupération de données d'une requète HTTP.

### Construction

- `create(): static` Crée une requète vide, à remplir manuellement (tests, CLI).
- `createFromServer(): static` Crée une requète à partir des superglobales (`$_SERVER`, `$_GET`, `$_COOKIE`, `$_FILES` et le corps de la requète).

Les setters permettent de construire une requète à la main :

- `setUri(string $uri): static`
- `setServers(array $values): static`
- `setQueryParams(array $values): static`
- `setBodyParams(array $values): static`
- `setAttachements(array $values): static`
- `setCookies(array $values): static`

### Données du serveur

- `servers(): array` Retourne un tableau des paramètres serveur (`$_SERVER`).
- `server(string $name): mixed` Retourne un paramètre serveur.
- `time(): int` Retourne l'horodatage en secondes de la requète.
- `mtime(): float` Retourne l'horodatage en secondes de la requète avec une précision à la microseconde.

### Méthode HTTP

- `method(): string` Retourne la méthode HTTP de la requète (CLI, GET, POST, DELETE, ...).
- `isGet(): bool` Retourne vrai pour une requète HTTP GET.
- `isPost(): bool` Retourne vrai pour une requète HTTP POST.
- `isPut(): bool` Retourne vrai pour une requète HTTP PUT.
- `isPatch(): bool` Retourne vrai pour une requète HTTP PATCH.
- `isDelete(): bool` Retourne vrai pour une requète HTTP DELETE.
- `isClient(): bool` Retourne vrai pour une requète depuis la ligne de commande.
- `isAjax(): bool` Retourne vrai pour une requète AJAX.
- `isSecure(): bool` Retourne vrai pour une requète HTTPS.

### URL

- `uri(): string` Retourne l'URI demandée par la requète.
- `baseUrl(): string` Retourne l'URL de base du site (schéma et hôte).
- `currentUrl(bool $withQueryString = true): string` Retourne l'URL complète de la requète.

### Cookies

- `cookies(): array` Retourne un tableau des cookies.
- `cookie(string $name): mixed` Retourne la valeur d'un cookie.

### Données de la requète

- `queryParams(): array` Retourne un tableau des paramètres de la requète.
- `queryParam(string $name): mixed` Retourne la valeur d'un paramètre de la requète.
- `bodyParams(): array` Retourne un tableau des données du corps de la requète d'un formulaire ou JSON.
- `bodyParam(string $name): mixed` Retourne la valeur d'une donnée du corps de la requète d'un formulaire ou JSON.
- `attachements(): array` Retourne un tableau des données des fichiers envoyés. Indexé par le nom du champ.
- `attachement(string $name): ?array` Retourne les données d'un fichier envoyé.

### Client

- `userAgent(): ?string` Retourne le user agent.
- `ip(): string` Retourne l'adresse IP.

## Response

`Bredala\Http\Response` Créer une réponse HTTP.

### Construction

- `create(): static` Crée une réponse avec les valeurs par défaut.
- `createFromServer(): static` Crée une réponse alignée sur la requète courante : version du protocole, cookies sécurisés en HTTPS et réglages CORS déduits des entêtes `Origin` et `Access-Control-Request-*`.
- `reset(): static` Réinitialise la réponse. La factory de flux et les réglages (cookies, CORS, buffer) sont conservés.

### Configuration

- `setStreamFactory(StreamFactoryInterface $factory): static` Définit la factory PSR-17 utilisée pour construire le corps.
- `getStreamFactory(): StreamFactoryInterface` Retourne la factory, détectée automatiquement si aucune n'a été définie.
- `setBuffer(int $buffer): static` Définit la taille du tampon utilisé à l'envoi du corps.
- `setCookieDomain(string $domain = ''): static`
- `setCookiePath(string $path = '/'): static`
- `setCookieSecure(bool $secure = true): static`
- `setCookieHttponly(bool $httponly = true): static`
- `setCookieSamesite(string $samesite = ''): static`
- `setCorsOrigin(string $origin = '*'): static`
- `setCorsMethods(string $methods = 'GET,POST,PUT,PATCH,DELETE,OPTIONS'): static`
- `setCorsHeaders(string $headers = '*'): static`

### HTTP status

- `getProtocolVersion(): string` Retourne la version du protocole HTTP.
- `setProtocolVersion(string $version): static` Définit la version du protocole HTTP.
- `getStatusCode(): int` Retourne le statut HTTP de la réponse.
- `setStatusCode(int $code, ?string $reason = null): static` Définit le statut HTTP de la réponse. Si la raison est nulle, une valeur par défaut correspondant au code HTTP sera utilisée.

Les codes HTTP sont disponibles en constantes sur la réponse, et les libellés associés dans `Response::$statusReasons` (les deux viennent de `Bredala\Http\HttpStatusTrait`) :

```php
$res->setStatusCode(Response::HTTP_NOT_FOUND); // 404 Not Found
```

### HTTP headers

- `getHeaders(): array` Retourne toutes les entêtes.
- `getHeader(string $header): array` Retourne les valeurs d'une entête.
- `hasHeader(string $header): bool` Retourne si une entête est définie.
- `addHeader(string $name, string $value, bool $replace = false): static` Ajoute une entête HTTP. Avec `$replace`, remplace les valeurs existantes au lieu de s'y ajouter.
- `removeHeader(string $name): static` Supprime une entête HTTP.
- `setContentType(string $mime, string $charset = "UTF-8"): static` Définit le type.
    ````php
    $res->setContentType('jpg'); // shortcut
    $res->setContentType('image/jpeg'); // verbose
    ````
- `addCookie(string $name, $value, int $expire = 0, $settings = []): static` Ajoute un cookie. `$settings` surcharge ponctuellement les réglages globaux (`domain`, `path`, `secure`, `httponly`, `samesite`).
- `removeCookie(string $name, $settings = []): static` Supprime un cookie. Le navigateur n'identifiant un cookie que par son nom, son domaine et son chemin, `$settings` doit reprendre ceux passés à `addCookie()`.
- `redirect(string $url = "/", bool $temporary = true): static` Redirection HTTP (302 par défaut, 301 si `$temporary` est faux). Réinitialise la réponse au passage : seuls les cookies déjà ajoutés sont conservés.
- `cache(int $age = 86400): static` Raccourci pour configurer le cache HTTP.
- `noCache(): static` Désactive le cache HTTP.
- `cors(?string $origin = null, ?string $methods = null, ?string $headers = null): static` Émet les entêtes CORS. Chaque argument nul retombe sur le réglage correspondant.

### Http body

- `getBody(): StreamInterface` Retourne le corps de la réponse.
- `setBody(StreamInterface|resource|string|Stringable|null $body = ""): static` Ajoute un contenu à la réponse. Un `StreamInterface` est utilisé tel quel ; tout autre type est refusé par une `InvalidArgumentException`.
- `setBodyFile(string $filename, string $mode = 'r'): static` Ajoute un fichier comme contenu, en flux, sans le charger en mémoire.
- `setText(string $data = ''): static` Convertit une chaîne en text/plain.
- `setJson(mixed $data = null, int $flags = 0): static` Convertit une donnée en application/json. Encode avec `JSON_THROW_ON_ERROR` : une donnée non sérialisable lève une `JsonException`, pas une `ResponseException`.
- `setJsonException(ResponseException $ex): static` Convertit une `ResponseException` en application/json et applique son statut à la réponse.

### Rendering

- `emitHeaders(): void` Envoi les entêtes au navigateur.
- `emitBody(?int $bufferLength = null): void` Envoi le contenu au navigateur.
- `emit(?int $bufferLength = null): void` Envoi entêtes et contenu au navigateur.

## Session

`Bredala\Http\Session` Couche d'abstraction pour manipuler les sessions.

- `__construct(?\SessionHandlerInterface $handler = null, array $options = [])` Le constructeur accepte un gestionnaire de session optionnel (memcache, db, etc) et des options de cookie : `name` (`PHPSESSID`), `lifetime` (`7200`), `path` (`/`), `domain` (`''`), `secure` (`false`), `httponly` (`true`), `samesite` (`Lax`).
- `start(?string $id = null): static` Démarre une session, éventuellement sur un identifiant donné. À appeler à chaque requète.
- `id(): ?string` Retourne l'identifiant de la session courante.
- `regenerate(bool $deleteOld = true): static` Régénère l'identifiant en conservant les données. À appeler après une connexion (protection contre la fixation de session).
- `close(): static` Ecrit et ferme la session.
- `destroy(): static` Détruit une session.
- `reset(): static` Supprime toutes les variables sessions.
- `all()` Retourne toutes les données.
- `has(string $name): bool` Retourne si une donnée existe en session.
- `get(string $name, mixed $default = null): mixed` Récupère la valeur d'une session.
- `set(string $name, mixed $value): static` Ajoute une donnée à la session.
- `delete(string $name): static` Supprime une donnée de la session.

### Flash data

Une donnée flash existe jusqu'a la prochaine requète (a moins de la re-marquer en flash).

- `setFlash(string $name, mixed $value): static`
- `markFlash(string $name): static`
- `unmarkFlash(string $name)`

### Temp data

Une donnée temporaire existe une certaine durée.

- `setTemp(string $name, mixed $value, int $time = 300)`
- `markTemp(string $name, int $time = 300)`
- `unmarkTemp(string $name)`

## ResponseException

`Bredala\Http\ResponseException` Utilisée pour gérer globalement les erreurs des réponses HTTP.

Hérite de `Exception` et implémente `JsonSerializable`. Une seule exception couvre tous les statuts : le code HTTP est passé au constructeur et se relit via `getCode()`.

```php
public function __construct(int $status, string $message = 'default', ?Throwable $previous = null)
```

```php
throw new ResponseException(Response::HTTP_NOT_FOUND, 'Utilisateur introuvable');
```

### Données additionnelles

- `setErrors(array $errors): static` Configure les erreurs.
- `addError(string $key, mixed $value): static` Ajoute une erreur.
- `getErrors(): array` Retourne les erreurs.
- `setExtra(array $extra): static` Configure des données additionnelles.
- `addExtra(string $key, mixed $value): static` Ajoute une donnée additionnelle.
- `getExtra(): array` Retourne les données additionnelles.
- `jsonSerialize(): mixed` Retourne la représentation JSON de l'exception.

La sérialisation produit toujours les quatre mêmes clés :

```json
{
    "status": 422,
    "error": "Données invalides",
    "errors": {"email": "Format invalide"},
    "extra": []
}
```

`errors` et `extra` sont toujours sérialisés en tableau `[]` lorsqu'ils sont vides, jamais en objet `{}`. Ils deviennent un objet JSON dès qu'une clé y est ajoutée.

### Statuts courants

Les constantes de `Response` évitent d'écrire les codes à la main.

| Constante                          | Code | Usage                                                        |
| ---------------------------------- | ---- | ------------------------------------------------------------ |
| `Response::HTTP_BAD_REQUEST`       | 400  | Erreur provenant de l'utilisateur, erreur de validation.      |
| `Response::HTTP_UNAUTHORIZED`      | 401  | Informations d'authentification non valides.                  |
| `Response::HTTP_FORBIDDEN`         | 403  | Accès interdit.                                               |
| `Response::HTTP_NOT_FOUND`         | 404  | Ressource introuvable.                                        |
| `Response::HTTP_NOT_ACCEPTABLE`    | 406  | Format de réponse non négociable.                             |
| `Response::HTTP_GONE`              | 410  | Ressource expirée.                                            |
| `Response::HTTP_UNPROCESSABLE_ENTITY` | 422 | Entité bien formée mais sémantiquement invalide.           |
| `Response::HTTP_LOCKED`            | 423  | Ressource verrouillée.                                        |
| `Response::HTTP_INTERNAL_SERVER_ERROR` | 500 | Erreur d'exécution, telle une panne.                      |

### Utilisation

Lever l'exception depuis la logique métier, la rattraper une fois à la frontière :

```php
use Bredala\Http\Request;
use Bredala\Http\Response;
use Bredala\Http\ResponseException;

$req = Request::createFromServer();
$res = Response::createFromServer();

try {
    $user = $repository->find($req->queryParam('id'))
        ?? throw new ResponseException(Response::HTTP_NOT_FOUND, 'Utilisateur introuvable');

    $res->setJson($user);
} catch (ResponseException $ex) {
    $res->setJsonException($ex);
}

$res->emit();
```
