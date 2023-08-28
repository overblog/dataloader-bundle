<?php

$header = <<<EOF
This file is part of the OverblogDataLoaderBundle package.

(c) Overblog <http://github.com/overblog/>

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
EOF;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'array_syntax' => ['syntax' => 'short'],
        'no_unreachable_default_argument_value' => false,
        'heredoc_to_nowdoc' => false,
        'header_comment' => ['header' => $header],
    ])
    ->setRiskyAllowed(true)
    ->setFinder(
        PhpCsFixer\Finder::create()->in(__DIR__)
    )
    ;
