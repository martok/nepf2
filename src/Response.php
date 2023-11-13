<?php

namespace Nepf2;

use Sabre\HTTP\ResponseDecorator;

class Response extends ResponseDecorator
{
    const HTTP_RESPONSE_TEXT = [
        400 => 'Bad request',
        403 => 'Access denied',
        404 => 'Not found',
        500 => 'Internal Server Error'
    ];

    public static function create(): Response
    {
        return new self(new \Sabre\HTTP\Response());
    }

    public function redirect(string $uri, int $status = 301): void
    {
        $this->setStatus($status);
        $this->setHeader('Location', $uri);
    }

    public function standardResponse(int $code): void
    {
        $this->setStatus($code);
        if (isset(self::HTTP_RESPONSE_TEXT[$code]))
            $this->setBody('<h1>' . self::HTTP_RESPONSE_TEXT[$code] . '</h1>');
    }

    public function setJSON(array $object, bool $adjustContentType = true): void
    {
        if ($adjustContentType)
            $this->setHeader('Content-Type', 'application/json');
        $this->setBody(json_encode($object));
    }
}