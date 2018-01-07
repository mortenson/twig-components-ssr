[![Build Status](https://travis-ci.org/mortenson/twig-components-ssr.svg?branch=master)](https://travis-ci.org/mortenson/twig-components-ssr)

# Twig Components Server Side Renderer

This project provides a server side renderer for [Twig Components](https://github.com/mortenson/twig-components)
using PHP.

Server side rendering Web Components or any Javascript _thing_ gives you:

1. Faster time to first meaningful paint
1. Better SEO, for tools without Javascript support
1. Progressive enhancement support
1. Avoidance of FOUC-related issues

# Installation

```
composer require mortenson/twig-components-ssr
```

# Usage

The renderer takes an array of Twig templates keyed by custom element (tag)
name, and then renders Twig Components in an HTML string.

Here's a basic example:

```php
$templates = [
  'my-component' => 'Hello {{ name }}!',
];
$html = '<my-component name="World"></my-component>';
$renderer = new TwigComponentsSSR\Renderer($templates);
$rendered_html = $renderer->render($html);
```

At this point, `$rendered_html` should contain:

```html
<my-component name="World" data-ssr="true">Hello World!</my-component>
```

When this HTML is displayed in a browser, and the Javascript for the Twig
Component is loaded, the server side rendered content should be hidden as soon
as the Shadow DOM root is attached.

Note that if you're using [generator-twig-components-webpack](https://github.com/mortenson/generator-twig-components-webpack),
a `templates.json` file is included in the `dist` directory and can be used
directly with the renderer.

# Inlining of styles

If a Twig Component template contains a `<style>` tag, which is a common way to
have encapsulated styles using Shadow DOM, the renderer will inline CSS after
the template is processed.

# Running tests

Tests for this project are written using PHPUnit. To execute tests, run:

```
./vendor/bin/phpunit --bootstrap vendor/autoload.php tests
```

# Coding standards

This project uses the PSR2 coding standard. To check for violations, run:

```
phpcs --standard=PSR2 src/*
```

# Todo

- [x] Support the `<slot />` element and named slots
