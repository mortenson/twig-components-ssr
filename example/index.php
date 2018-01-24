<?php

require('../vendor/autoload.php');

use TwigComponentsSSR\Renderer;

$templates = json_decode(file_get_contents('https://unpkg.com/twig-components-example@0.0.3/dist/templates.json'), TRUE);
$html = file_get_contents('./index.html');
$renderer = new Renderer($templates);

echo $renderer->render($html);
