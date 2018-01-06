<?php

namespace TwigComponentsSSR;

/**
 * Server side renders Twig Components in an HTML string.
 */
class Renderer
{
    /**
     * @var \Twig_Environment
     *   The Twig Environment.
     */
    public $twigEnvironment;

    /**
     * @var string[]
     *   An array of tag names supported by this class.
     */
    protected $tagNames;

    /**
     * TwigComponentsSSR constructor.
     *
     * @param array $templates
     *   An array of Twig Templates keyed by tag name.
     * @param \Twig_Environment $environment
     *   (optional) An optional Twig environment.
     */
    public function __construct($templates = [], \Twig_Environment $environment = null)
    {
        $loader = new \Twig_Loader_Array($templates);
        if (!$environment) {
            $this->twigEnvironment = new \Twig_Environment($loader);
        } else {
            $environment->setLoader($loader);
            $this->twigEnvironment = $environment;
        }
        $this->tagNames = array_keys($templates);
    }

    /**
     * Renders Twig Components in an HTML string.
     *
     * @param  string $html
     *   An HTML string.
     * @return string
     *   The HTML string with rendered Twig Components.
     */
    public function render($html)
    {
        $document = new \DOMDocument();
        $document->formatOutput = false;
        $document->strictErrorChecking = false;
        @$document->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $this->renderTwigComponents($document, $document);
        return trim($document->saveHTML());
    }

    /**
     * Renders Twig Components recursively based on a DOM entrypoint.
     *
     * @param \DOMElement|\DOMDocument &$element
     *   The element to parse and traverse.
     * @param \DOMDocument $document
     *   The original, unmodified DOMDocument.
     */
    protected function renderTwigComponents(&$element, $document)
    {
        foreach ($this->tagNames as $tag_name) {
            /** @var \DOMElement $tag */
            foreach ($element->getElementsByTagName($tag_name) as $tag) {
                if ($tag->hasAttribute('ssr')) {
                    continue;
                }
                $context = $this->getContext($tag);
                $render = $this->twigEnvironment->render($tag_name, $context);
                $inliner = new CSSInliner();
                $render = $inliner->convert($render);
                // @todo Support unnamed and named slots.
                $child = $document->createDocumentFragment();
                $child->appendXML($render);
                $tag->appendChild($child);
                $tag->setAttribute('ssr', 'true');
                $this->renderTwigComponents($tag, $document);
            }
        }
    }

    /**
     * Parses Twig template context from a DOM element's attributes.
     *
     * @param \DOMElement $tag
     *   The DOMElement tag.
     * @return array
     *   An array of template context.
     */
    protected function getContext($tag)
    {
        $context = [];
        /** @var \DOMAttr $attribute */
        foreach ($tag->attributes as $attribute) {
            $attribute_name = str_replace('-', '_', $attribute->name);
            $context[$attribute_name] = $attribute->value;
        }
        return $context;
    }
}
