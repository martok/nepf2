<?php
/**
 * Nepf2 Framework - Router
 *
 * @link       https://github.com/martok/nepf2
 * @copyright  Copyright (c) 2023- Martok & Contributors.
 * @license    Apache License
 */

namespace Nepf2\Router;

use Elephox\Mimey\MimeType;
use Nepf2\Application;
use Nepf2\Request;
use Nepf2\Response;
use Nepf2\Util\Path;

class StaticMatcher extends RouteMatcher
{
    private const CACHE_SECONDS = 365*24*3600;

    public string $localPath;
    public bool $isDirectory;
    /** @var string[] $filters  */
    public array $filters = [];
    private ?string $queryPath = null;

    public function __construct()
    {
        $this->setMethod(['GET']);
    }

    public function setPath(string $fragment, bool $recursive): void
    {
        $fixedLen = strlen($fragment);
        $expr = preg_quote($fragment, '%');
        $this->isDirectory = str_ends_with($fragment, '/');
        if ($this->isDirectory && $recursive) {
            $expr .= '(.*[^/])';
        }
        $this->expression = '%^' . $expr . '$%';
        $this->fixedLength = $fixedLen;
    }

    public function setFilter(string $filter): void
    {
        if (!$filter) {
            $filter = '*';
        }
        $this->filters = explode(';', $filter);
    }

    public function matchPath(string $path): bool
    {
        if (1 === preg_match($this->expression, $path, $vars)) {
            if ($this->isDirectory && ($query = $vars[1])) {
                if ($this->matchFilter(basename($query))) {
                    $this->queryPath = $query;
                    return true;
                }
            } else {
                return true;
            }
        }
        return false;
    }

    protected function matchFilter(string $filename): bool
    {
        foreach ($this->filters as $filter) {
            if (fnmatch($filter, $filename, FNM_CASEFOLD))
                return true;
        }
        return false;
    }

    public function executeRoute(Application $app, Request $request, Response $response): bool
    {
        if ($this->isDirectory) {
            // a directory-like route needs a file name
            if (is_null($this->queryPath)) {
                $response->standardResponse(404);
                return true;
            }
            $norm = Path::Canonicalize($this->queryPath);
            $expanded = Path::ExpandRelative($this->localPath, $norm);
        } else {
            // a file-like route can not have a sub-path
            assert(is_null($this->queryPath));
            $expanded = $this->localPath;
        }

        if (!is_file($expanded)) {
            $response->standardResponse(404);
            return true;
        }

        // guess content-type
        $contentType = MimeType::ApplicationOctetStream;
        if (($ext = pathinfo($expanded, PATHINFO_EXTENSION)) &&
            ($mime = MimeType::tryFromExtension(strtolower($ext)))) {
            $contentType = $mime;
        }

        // send file with long cache
        $response->setHeader('Cache-Control', 'private, max-age=' . self::CACHE_SECONDS . ', immutable');
        $response->setHeader('Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + self::CACHE_SECONDS));
        $response->setHeader('Content-Type', $contentType->value);
        $response->setBody(fopen($expanded, 'r'));
        return true;
    }
}