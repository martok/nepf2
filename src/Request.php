<?php

namespace Nepf2;

use Sabre\HTTP\RequestDecorator;
use Sabre\HTTP\Sapi;
use Sabre\Uri;

class Request extends RequestDecorator
{
    private ?array $queryParams = null;

    public static function create(): Request
    {
        return new self(Sapi::getRequest());
    }

    public function uri(): array
    {
        return Uri\parse($this->getAbsoluteUrl());
    }

    public function getAbsoluteBase(): string
    {
        $p = $this->getUrl();
        $a = $this->getAbsoluteUrl();
        if (str_ends_with($a, $p))
            return substr($a, 0, -strlen($p));
        return $this->getBaseUrl();
    }

    public function setUrl(string $url): void
    {
        parent::setUrl($url);
        $this->queryParams = null;
    }

    public function getQueryParameters(): array
    {
        return $this->queryParams ??= parent::getQueryParameters();
    }

    public function setQueryParam(string $param, string $value): void
    {
        $this->getQueryParameters();
        $this->queryParams[$param] = $value;
    }

    public function jsonBody(): array
    {
        return json_decode($this->getBodyAsString(), associative: true) ?? [];
    }

    private function param(string $name): null|string|array
    {
        return $this->getPostData()[$name] ?? $this->getQueryParameters()[$name] ?? null;
    }

    public function int(string $name, int $default = 0, bool $nonempty = false): int
    {
        $val = $this->param($name);
        if (is_null($val) || is_array($val))
            return $default;
        if ($nonempty && empty($val))
            return $default;
        return (int)$val;
    }

    public function bool(string $name, bool $default = false): bool
    {
        $val = $this->param($name);
        if (is_null($val))
            return $default;
        return (bool)$val;
    }

    public function str(string $name, string $default = '', bool $nonempty = false): string
    {
        $val = $this->param($name);
        if (is_null($val) || is_array($val))
            return $default;
        if ($nonempty && empty($val))
            return $default;
        return (string)$val;
    }

    public function arr(string $name, array $default = [], bool $nonempty = false): array
    {
        $val = $this->param($name);
        if (!is_array($val))
            return $default;
        if ($nonempty && empty($val))
            return $default;
        return $val;
    }
}