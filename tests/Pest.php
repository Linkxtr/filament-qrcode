<?php

declare(strict_types=1);

use Linkxtr\FilamentQrCode\Tests\TestCase;

require_once __DIR__.'/Support/Overrides.php';

pest()
    ->extend(TestCase::class)
    ->afterEach(function (): void {
        $GLOBALS['mockTempnamFalse'] = false;
        $GLOBALS['mockTempnamPath'] = null;
        $GLOBALS['lastUnlinkedFile'] = null;
    })
    ->in(__DIR__);
