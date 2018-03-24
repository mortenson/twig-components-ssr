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

The renderer takes an array of Twig template names keyed by custom element
name, and a Twig Environment that can properly render those templates.

Here's a basic example with the array loader:

```php
$templates = [
  'my-component.twig' => 'Hello {{ name }}!',
];
$loader = new \Twig_Loader_Array($templates);
$environment = new \Twig_Environment($loader);
$tag_templates = ['my-component' => 'my-component.twig'];
$renderer = new Renderer($tag_templates, $environment);
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

# Handling of style tags

If a Twig Component template contains a `<style>` tag, which is a common way to
have encapsulated styles using Shadow DOM, the renderer will store the contents
of the tag and remove it from the component.

When all component rendering is complete, those stored styles will have the tag
name prepended to them, and all rules will become `!important`;

This method does not guarantee Shadow DOM-like style encapsulation, but is a
good start.

# Support for slots

Default and named slots are fully supported by this renderer.

If a component named `my-component` uses a template like:

```
{{ prefix }} <slot />
```

rendering this:

```
<my-component prefix="Hello">World!</my-component>
```

would result in:

```
<my-component prefix="Hello" data-ssr-content="World!">Hello World!</my-component>
```

The `data-ssr-content` attribute contains the original, untouched content of
the element. The base component will use this original content before attaching
the shadow root to ensure proper future rendering.

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

# Example page

A super light-weight example is available at /example. You can check it out
by running `cd example && php -S 127.0.0.1:12345`, then visiting
`http://127.0.0.1:12345` in your browser.

# Todo

- [x] Support the `<slot />` element and named slots
