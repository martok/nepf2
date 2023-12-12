# Nepf2 - Noch ein PHP Framework 2 (Yet Another PHP Framework 2)

## Info
Modern PHP Framework written to take advantage of PHP 8.2 language features.
It tries not to get in your way and instead help to keep boilerplate to a minimum.

## Usage

Require it using composer:

    composer require martok/nepf2

You may want to add a `vcs`-type repository to get the current version directly from GitHub:

    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/martok/nepf2"
        }
    ],

## Credits

* The request/response handling is done using `sabre/http`, [Link](https://sabre.io/http/).
* Templates are rendered using `twig`, [Link](https://twig.symfony.com/).
* Database access is managed by the `pop-db` component of [Pop PHP](https://www.popphp.org/), [Link](https://github.com/popphp/pop-db).

Nepf2 follows a framework with the same name written back in 2015 for PHP 5.3. This one's better, though.
