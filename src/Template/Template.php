<?php
/**
 * Nepf2 Framework - Template
 *
 * @link       https://github.com/martok/nepf2
 * @copyright  Copyright (c) 2023- Martok & Contributors.
 * @license    Apache License
 */

namespace Nepf2\Template;

use Nepf2\Application;
use Nepf2\IComponent;
use Nepf2\Util\Arr;
use Twig;

class Template implements IComponent
{
    public const ComponentName = "tpl";
    private Application $app;
    private ?Twig\Environment $twig = null;

    public function __construct(Application $application)
    {
        $this->app = $application;
    }

    /**
     * @param array $config {
     * @type string|bool $cache Path to cache directory, or false.
     *                                 Default false.
     * @type string|string[] $templates Path or list of paths for template directory, relative
     *                                          to Application::getRoot().
     * }
     * @return void
     */
    public function configure(array $config)
    {
        $config = Arr::ExtendConfig([
            'cache' => false,
            'templates' => 'app/view'
        ], $config);
        $cache_opt = is_string($config['cache']) ? realpath($config['cache']) : false;
        $template_dirs = $config['templates'];
        if (!is_array($template_dirs))
            $template_dirs = [$template_dirs];
        $loader = new Twig\Loader\FilesystemLoader($template_dirs, $this->app->getRoot());
        $twig = new Twig\Environment($loader, [
            'cache' => $cache_opt
        ]);
        $this->addTwigFilters($twig);
        $this->twig = $twig;
    }

    public function render(string $name, array $context = []): string
    {
        return $this->twig->render($name, $context);
    }

    public function view(string $name): TemplateView
    {
        return new TemplateView($this->twig, $name);
    }

    public static function formatFileSize($size): string
    {
        if (!is_numeric($size))
            $size = (int)$size;

        $units = array(
            1024**4 => 'TiB',
            1024**3 => 'GiB',
            1024**2 => 'MiB',
            1024 => 'KiB'
        );

        foreach ($units as $unit => $val) {
            if ($size < $unit * 1.1) continue;
            return sprintf('%.1f %s', $size / $unit, $val);
        }
        return sprintf('%d Bytes', $size);
    }

    private function addTwigFilters(Twig\Environment $env): void
    {
        $env->addFilter(new Twig\TwigFilter('filesize', self::formatFileSize(...)));

    }
}