<?php

require('../vendor/autoload.php');

use TwigComponentsSSR\Renderer;

$template = file_get_contents('https://unpkg.com/twig-components-example@0.0.4/dist/templates/tce-hero.twig', TRUE);
$loader = new \Twig_Loader_Array([
  'tce-hero.twig' => $template,
]);
$environment = new \Twig_Environment($loader);

$renderer = new Renderer(['tce-hero' => 'tce-hero.twig'], $environment);

$html = file_get_contents('./index.html');

echo $renderer->render($html);
