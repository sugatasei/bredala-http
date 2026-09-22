<?php

use Bredala\Http\Response;
use Bredala\Http\ResponseException;
use PHPUnit\Framework\TestCase;

class ExceptionTest extends TestCase
{
    public function testDefaults()
    {
        $ex = new ResponseException(418);

        self::assertInstanceOf(Exception::class, $ex);
        self::assertInstanceOf(JsonSerializable::class, $ex);
        self::assertSame(418, $ex->getCode());
        self::assertSame('default', $ex->getMessage());
        self::assertNull($ex->getPrevious());
        self::assertSame([], $ex->getErrors());
        self::assertSame([], $ex->getExtra());
    }

    /**
     * @dataProvider statusProvider
     */
    public function testStatus(int $status, string $message)
    {
        $ex = new ResponseException($status, $message);

        self::assertSame($status, $ex->getCode());
        self::assertSame($message, $ex->getMessage());
    }

    public static function statusProvider(): array
    {
        return [
            'user' => [400, 'bad request'],
            'access' => [401, 'unauthorized'],
            'forbidden' => [403, 'forbidden'],
            'empty' => [404, 'not found'],
            'not acceptable' => [406, 'not acceptable'],
            'expired' => [410, 'gone'],
            'lock' => [423, 'locked'],
            'system' => [500, 'server error'],
        ];
    }

    public function testPrevious()
    {
        $previous = new RuntimeException('root cause');
        $ex = new ResponseException(500, 'system', $previous);

        self::assertSame($previous, $ex->getPrevious());
    }

    public function testErrors()
    {
        $ex = new ResponseException(400);

        self::assertSame($ex, $ex->setErrors(['name' => 'setName', 'age' => 'setAge']));
        self::assertSame($ex, $ex->addError('name', 'addName'));
        $ex->addError('city', 'addCity');

        self::assertSame([
            'name' => 'addName',
            'age' => 'setAge',
            'city' => 'addCity',
        ], $ex->getErrors());
    }

    public function testErrorsAreReplaced()
    {
        $ex = new ResponseException(400);

        $ex->addError('name', 'addName');
        $ex->setErrors(['age' => 'setAge']);

        self::assertSame(['age' => 'setAge'], $ex->getErrors());
    }

    public function testExtra()
    {
        $ex = new ResponseException(400);

        self::assertSame($ex, $ex->setExtra(['name' => 'setName', 'age' => 'setAge']));
        self::assertSame($ex, $ex->addExtra('name', 'addName'));
        $ex->addExtra('city', 'addCity');

        self::assertSame([
            'name' => 'addName',
            'age' => 'setAge',
            'city' => 'addCity',
        ], $ex->getExtra());
    }

    public function testExtraIsReplaced()
    {
        $ex = new ResponseException(400);

        $ex->addExtra('name', 'addName');
        $ex->setExtra(['age' => 'setAge']);

        self::assertSame(['age' => 'setAge'], $ex->getExtra());
    }

    public function testJsonSerialize()
    {
        $ex = new ResponseException(422, 'invalid');
        $ex->addError('name', 'required');
        $ex->addExtra('trace', 'abc');

        $json = json_encode($ex, JSON_THROW_ON_ERROR);
        self::assertJson($json);

        self::assertSame([
            'status' => 422,
            'error' => 'invalid',
            'errors' => ['name' => 'required'],
            'extra' => ['trace' => 'abc'],
        ], json_decode($json, true));
    }

    public function testJsonSerializeEmpty()
    {
        $ex = new ResponseException(500);

        self::assertSame([
            'status' => 500,
            'error' => 'default',
            'errors' => [],
            'extra' => [],
        ], $ex->jsonSerialize());
    }

    public function testEmptyErrorsAndExtraSerializeAsArrays()
    {
        $json = json_encode(new ResponseException(500), JSON_THROW_ON_ERROR);

        self::assertStringContainsString('"errors":[]', $json);
        self::assertStringContainsString('"extra":[]', $json);
        self::assertStringNotContainsString('"errors":{}', $json);
        self::assertStringNotContainsString('"extra":{}', $json);
    }

    public function testClearedErrorsAndExtraSerializeAsArrays()
    {
        $ex = new ResponseException(500);
        $ex->addError('name', 'required')->addExtra('trace', 'abc');
        $ex->setErrors([])->setExtra([]);

        self::assertStringContainsString('"errors":[]', json_encode($ex, JSON_THROW_ON_ERROR));
        self::assertStringContainsString('"extra":[]', json_encode($ex, JSON_THROW_ON_ERROR));
    }

    public function testIsThrowable()
    {
        $this->expectException(ResponseException::class);
        $this->expectExceptionCode(404);
        $this->expectExceptionMessage('not found');

        throw new ResponseException(404, 'not found');
    }

    public function testResponseSetJsonException()
    {
        $ex = new ResponseException(403, 'forbidden');
        $ex->addError('token', 'expired');

        $res = Response::create()->setJsonException($ex);

        self::assertSame(403, $res->getStatusCode());
        self::assertStringStartsWith('application/json', $res->getHeader('content-type')[0] ?? '');
        self::assertSame([
            'status' => 403,
            'error' => 'forbidden',
            'errors' => ['token' => 'expired'],
            'extra' => [],
        ], json_decode((string) $res->getBody(), true));
    }
}
