<?php

declare(strict_types=1);

use Pest\Rector\Set\PestSetList;
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;

return RectorConfig::configure()
    ->withoutParallel()
    ->withPaths([
        __DIR__.'/config',
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withSets([
        SetList::DEAD_CODE,
        SetList::CODE_QUALITY,
        SetList::CODING_STYLE,
        SetList::TYPE_DECLARATION,
        SetList::PRIVATIZATION,
        SetList::CARBON,
        PestSetList::CODING_STYLE,
    ])
    ->withPhpSets(php84: true)
    ->withAttributesSets()
    ->withImportNames(removeUnusedImports: true)
    ->withTreatClassesAsFinal()
    ->withFluentCallNewLine();
