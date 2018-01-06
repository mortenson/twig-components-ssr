<?php

namespace TwigComponentsSSR;

use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

/**
 * Inlines styles from a rendered Twig Component.
 */
class CSSInliner extends CssToInlineStyles
{
    /**
     * {@inheritdoc}
     */
    protected function createDomDocumentFromHtml($html)
    {
        $document = new \DOMDocument();
        $document->formatOutput = false;
        $document->strictErrorChecking = false;
        @$document->loadHTML('<body>' . $html . '</body>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        return $document;
    }

    /**
     * {@inheritdoc}
     */
    protected function getHtmlFromDocument(\DOMDocument $document)
    {
        foreach ($document->getElementsByTagName('style') as $tag) {
            $tag->parentNode->removeChild($tag);
        }
        $html = $document->saveHTML();
        $html = str_replace(['<body>', '</body>'], '', $html);

        return trim($html);
    }
}
