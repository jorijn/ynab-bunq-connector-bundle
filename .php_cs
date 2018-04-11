<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

return Config::create()
    ->setUsingCache(true)
    ->setRules([
        '@Symfony' => true,
        '@PSR2' => true,
        'ordered_class_elements' => false,
        'ordered_imports' => true,
        'array_syntax' => ['syntax' => 'short'],
        'phpdoc_add_missing_param_annotation' => true,
        'phpdoc_order' => true,
        'phpdoc_align' => true,
        'phpdoc_separation' => true,
        'no_useless_else' => true,
        'general_phpdoc_annotation_remove' => ['author']
    ])
    ->setFinder(
        Finder::create()
            ->in(__DIR__)
            ->name('*.php')
    );