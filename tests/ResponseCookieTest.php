<?php

use Bredala\Http\Response;
use PHPUnit\Framework\TestCase;

class ResponseCookieTest extends TestCase
{
    private function response(): Response
    {
        return Response::create()
            ->setCookieSecure(false)
            ->setCookieHttponly(false);
    }

    public function testAddCookie()
    {
        $res = $this->response()->addCookie('session', 'abc');

        self::assertSame(['session=abc; path=/'], $res->getHeader('Set-Cookie'));
    }

    public function testValueIsUrlEncoded()
    {
        $res = $this->response()->addCookie('session', 'a b&c');

        self::assertSame(['session=a+b%26c; path=/'], $res->getHeader('Set-Cookie'));
    }

    public function testRemoveCookieExpiresInThePast()
    {
        $res = $this->response()->removeCookie('session');
        $header = $res->getHeader('Set-Cookie')[0];

        self::assertStringStartsWith('session=;', $header);
        self::assertStringContainsString('expires=', $header);
    }

    public function testRemoveCookieMatchesDomainAndPath()
    {
        $settings = ['domain' => 'api.example.test', 'path' => '/v1'];

        $set = $this->response()->addCookie('session', 'abc', 0, $settings);
        $del = $this->response()->removeCookie('session', $settings);

        $attributes = fn(string $header) => array_slice(array_map('trim', explode(';', $header)), 1);

        self::assertSame(
            $attributes($set->getHeader('Set-Cookie')[0]),
            array_values(array_filter(
                $attributes($del->getHeader('Set-Cookie')[0]),
                fn($a) => !str_starts_with($a, 'expires=')
            ))
        );
    }

    public function testCookieSettingsAreApplied()
    {
        $res = Response::create()
            ->setCookieDomain('example.test')
            ->setCookiePath('/app')
            ->setCookieSamesite('Lax')
            ->addCookie('session', 'abc');

        self::assertSame(
            ['session=abc; domain=example.test; path=/app; secure; HttpOnly; SameSite=Lax'],
            $res->getHeader('Set-Cookie')
        );
    }

    public function testSettingsCanBeOverriddenPerCookie()
    {
        $res = $this->response()
            ->setCookiePath('/app')
            ->addCookie('theme', 'dark', 0, ['path' => '/']);

        self::assertSame(['theme=dark; path=/'], $res->getHeader('Set-Cookie'));
    }

    public function testCookiesSurviveRedirect()
    {
        $res = $this->response()->addCookie('session', 'abc')->setText('discarded');
        $res->redirect('/home');

        self::assertSame(['session=abc; path=/'], $res->getHeader('Set-Cookie'));
        self::assertSame(302, $res->getStatusCode());
        self::assertSame('', (string) $res->getBody());
    }
}
