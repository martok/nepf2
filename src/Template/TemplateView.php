<?php
/**
 * Nepf2 Framework - Template
 *
 * @link       https://github.com/martok/nepf2
 * @copyright  Copyright (c) 2023- Martok & Contributors.
 * @license    Apache License
 */

namespace Nepf2\Template;

use Nepf2\Util\Arr;
use Twig\Environment;

class TemplateView
{
    private readonly Environment $twig;
    private string $templateName;
    private array $context;

    public function __construct(Environment $twig, string $templateName)
    {
        $this->twig = $twig;
        $this->templateName = $templateName;
        $this->context = [];
    }

    public function render(): string
    {
        return $this->twig->render($this->templateName, $this->context);
    }

    public function set(string $slug, mixed $value): void
    {
        Arr::DottedAccess($this->context, $slug, function (&$array, $key) use ($value) {
            // if both are array, merge them. otherwise fully replace
            if (isset($array[$key]) && is_array($array[$key]) && is_array($value)) {
                $array[$key] = array_merge_recursive($array[$key], $value);
            } else {
                $array[$key] = $value;
            }
        });
    }

    public function setAll(array $values): void
    {
        foreach ($values as $slug => $value) {
            $this->set($slug, $value);
        }
    }

    public function export(string $var, mixed $value): void
    {
        $this->set('exports.' . $var, $value);
    }

    public function get(string $slug): mixed
    {
        return Arr::DottedAccess($this->context, $slug);
    }


}