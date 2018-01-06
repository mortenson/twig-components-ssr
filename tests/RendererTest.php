<?php

namespace TwigComponentsSSR\Tests;

use PHPUnit\Framework\TestCase;
use TwigComponentsSSR\Renderer;

class RendererTest extends TestCase
{
    /**
     * @dataProvider rendererDataProvider
     */
    public function testRenderer($templates, $html, $expected)
    {
        $renderer = new Renderer($templates);
        $this->assertSame($renderer->render($html), $expected);
    }

    public function rendererDataProvider()
    {
        return [
            'basic' => [
                [
                    'my-component' => 'Hello {{ name }}!'
                ],
                '<my-component name="World"></my-component>',
                '<my-component name="World" ssr="true">Hello World!</my-component>',
            ],
            'nested' => [
                [
                    'my-component' => 'Hello <my-name name="{{ name }}"></my-name>!',
                    'my-name' => '<b>World</b>'
                ],
                '<my-component name="World"></my-component>',
                '<my-component name="World" ssr="true">Hello <my-name name="World" ssr="true"><b>World</b></my-name>!</my-component>',
            ],
            'styles' => [
                [
                    'my-component' => '<style>p { color: blue; }</style>Hello <p>{{ name }}</p>!'
                ],
                '<my-component name="World"></my-component>',
                '<my-component name="World" ssr="true">Hello <p style="color: blue;">World</p>!</my-component>',
            ],
        ];
    }
}
