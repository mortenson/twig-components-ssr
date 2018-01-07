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
                if ($tag->hasAttribute('data-ssr')) {
                    continue;
                }
                $context = $this->getContext($tag);
                $render = $this->twigEnvironment->render($tag_name, $context);
                $inliner = new CSSInliner();
                $render = $inliner->convert($render);
                $this->preserveChildNodes($tag, $document);
                $newContent = $document->createDocumentFragment();
                $newContent->appendXML($render);
                $this->renderSlots($newContent, $tag, $document);
                $tag->textContent = '';
                $tag->appendChild($newContent);
                $tag->setAttribute('data-ssr', 'true');
                $this->renderTwigComponents($tag, $document);
            }
        }
    }

    /**
     * Replaces slots form a rendered template with existing content.
     *
     * @param \DOMDocumentFragment &$newContent
     *   The rendered Twig template as a document fragment.
     * @param \DOMElement &$tag
     *   The unmodified DOMElement tag.
     * @param \DOMDocument $document
     *   The DOMDocument.
     */
    protected function renderSlots($newContent, $tag, $document) {
        $xpath = new \DOMXPath($document);
        $oldContent = $tag->cloneNode(true);
        $default_slot = false;
        /** @var \DOMNode $slot */
        foreach ($xpath->query('.//slot', $newContent) as $slot) {
            if (!isset($slot->attributes['name'])) {
                $default_slot = $default_slot ?: $slot;
                continue;
            }
            $expression = './/*[@slot="' . $slot->attributes['name']->value . '"]';
            $replacement = $document->createDocumentFragment();
            $matches = $xpath->query($expression, $oldContent);
            /** @var \DOMNode $match */
            foreach ($matches as $match) {
                $replacement->appendChild($match->cloneNode(true));
                $match->parentNode->removeChild($match);
            }
            if (!$replacement->hasChildNodes()) {
                $replacement->appendXML($this->getChildHTML($slot, $document));
            }
            $slot->parentNode->replaceChild($replacement->cloneNode(true), $slot);
        }
        $replacement = $document->createDocumentFragment();
        $replacement->appendXML($this->getChildHTML($oldContent, $document));
        if ($default_slot) {
            if (!$replacement->hasChildNodes()) {
                $replacement->appendXML($this->getChildHTML($default_slot, $document));
            }
            $default_slot->parentNode->replaceChild($replacement->cloneNode(true), $default_slot);
        }
        foreach ($xpath->query('.//slot', $newContent) as $slot) {
            $slot->parentNode->removeChild($slot);
        }
    }

    /**
     * Ensures that child nodes of a component are preserved in an attribute.
     *
     * @param \DOMNode $node
     *   The DOMNode
     * @param \DOMDocument $document
     *   The DOMDocument.
     *
     * @return string
     *   The HTML of all the child nodes.
     */
    protected function getChildHTML($node, $document) {
        $html = '';
        foreach ($node->childNodes as $child) {
            $html .= $document->saveHTML($child);
        }
        return $html;
    }

    /**
     * Ensures that child nodes of a component are preserved in an attribute.
     *
     * @param \DOMElement &$tag
     *   The DOMElement tag.
     * @param \DOMDocument $document
     *   The DOMDocument.
     */
    protected function preserveChildNodes(&$tag, $document) {
        if ($tag->hasChildNodes()) {
            $original_content = $this->getChildHTML($tag, $document);
            $tag->setAttribute('data-ssr-content', json_encode($original_content));
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
