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
        $loader = new \Twig_Loader_Array($templates);
        $environment = new \Twig_Environment($loader);
        $tag_templates = array_combine(array_keys($templates), array_keys($templates));
        $renderer = new Renderer($tag_templates, $environment);
        $this->assertSame($renderer->render('<wrapper>' . $html . '</wrapper>'), '<wrapper>' . $expected . '</wrapper>');
        $this->assertSame(array_values($renderer->getRenderedTags()), array_keys($templates));
    }

    public function rendererDataProvider()
    {
        return [
            'basic' => [
                [
                    'my-component' => 'Hello {{ name }}!'
                ],
                '<my-component name="World"></my-component>',
                '<my-component name="World" data-ssr-content=\'""\' data-ssr="true">Hello World!</my-component>',
            ],
            'nested' => [
                [
                    'my-component' => 'Hello <my-name name="{{ name }}"></my-name>!',
                    'my-name' => '<b>World</b>'
                ],
                '<my-component name="World"></my-component>',
                '<my-component name="World" data-ssr-content=\'""\' data-ssr="true">Hello <my-name name="World" data-ssr-content=\'""\' data-ssr="true"><b>World</b></my-name>!</my-component>',
            ],
            'styles' => [
                [
                    'my-component' => '<style>p { color: blue; }</style>Hello <p>{{ name }}</p>!'
                ],
                '<my-component name="World"></my-component>',
                '<style>my-component p {color: blue !important;}</style><my-component name="World" data-ssr-content=\'""\' data-ssr="true">Hello <p>World</p>!</my-component>',
            ],
            'slot' => [
                [
                    'my-component' => '<slot></slot>'
                ],
                '<my-component><div class="foo">Hello World!</div></my-component>',
                '<my-component data-ssr-content=\'"&lt;div class=\"foo\"&gt;Hello World!&lt;\/div&gt;"\' data-ssr="true"><div class="foo">Hello World!</div></my-component>',
            ],
            'slot_placeholder' => [
                [
                    'my-component' => '<slot>placeholder</slot>'
                ],
                '<my-component></my-component>',
                '<my-component data-ssr-content=\'""\' data-ssr="true">placeholder</my-component>',
            ],
            'slot_named' => [
                [
                    'my-component' => 'Hello <slot name="name"></slot>'
                ],
                '<my-component><span slot="name">World!</span></my-component>',
                '<my-component data-ssr-content=\'"&lt;span slot=\"name\"&gt;World!&lt;\/span&gt;"\' data-ssr="true">Hello <span slot="name">World!</span></my-component>',
            ],
            'slot_complex' => [
                [
                    'my-component' => '<slot></slot><slot name="suffix"></slot><slot></slot><slot name="punctuation">!</slot>'
                ],
                '<my-component><div slot="suffix">, ya animal</div>Hello<p>World</p></my-component>',
                '<my-component data-ssr-content=\'"&lt;div slot=\"suffix\"&gt;, ya animal&lt;\/div&gt;Hello&lt;p&gt;World&lt;\/p&gt;"\' data-ssr="true">Hello<p>World</p><div slot="suffix">, ya animal</div>!</my-component>',
            ],
            'replace_existing_ssr_content' => [
                [
                    'my-component' => 'Hello {{ name }}!'
                ],
                '<my-component name="World" data-ssr-content="Replace me"></my-component><x-unknown data-ssr-content="Unsafe"></x-unknown>',
                '<my-component name="World" data-ssr-content=\'""\' data-ssr="true">Hello World!</my-component><x-unknown></x-unknown>',
            ],
            'style_host' => [
                [
                    'my-component' => '<style>:host { display: block; } :host(.foo) { display: none; }</style>Hello {{ name }}!'
                ],
                '<my-component name="World"></my-component>',
                '<style>my-component {display: block !important;}' . "\n" . 'my-component.foo {display: none !important;}</style><my-component name="World" data-ssr-content=\'""\' data-ssr="true">Hello World!</my-component>',
            ],
        ];
    }

  public function testFileLoader()
  {
    $loader = new \Twig_Loader_Filesystem([
      'templates/components',
      'templates/components/my-component',
    ], __DIR__);
    $environment = new \Twig_Environment($loader);
    $tag_templates = [
      'my-component' => 'my-component.twig',
    ];
    $renderer = new Renderer($tag_templates, $environment);
    $this->assertSame($renderer->render('<my-component name="World"></my-component>'), '<my-component name="World" data-ssr-content=\'""\' data-ssr="true">Hello World!</my-component>');
  }
}
