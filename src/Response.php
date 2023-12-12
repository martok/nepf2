<?php
/**
 * Nepf2 Framework
 *
 * @link       https://github.com/martok/nepf2
 * @copyright  Copyright (c) 2023- Martok & Contributors.
 * @license    Apache License
 */

namespace Nepf2;

use Sabre\HTTP;

class Response extends HTTP\ResponseDecorator
{
    public static function create(): Response
    {
        return new self(new HTTP\Response());
    }

    public function redirect(string $uri, int $status = 301): void
    {
        $this->setStatus($status);
        $this->setHeader('Location', $uri);
    }

    public function standardResponse(int $code): void
    {
        $this->setStatus($code);
        if (($code >= 400) && isset(HTTP\Response::$statusCodes[$code]))
            $this->setBody('<h1>' . HTTP\Response::$statusCodes[$code] . '</h1>');
    }

    public function setJSON(array $object, bool $adjustContentType = true): void
    {
        if ($adjustContentType)
            $this->setHeader('Content-Type', 'application/json');
        $this->setBody(json_encode($object));
    }
}