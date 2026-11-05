<?php

declare(strict_types=1);

namespace Linkxtr\FilamentQr\Actions {
    $GLOBALS['mockTempnamFalse'] ??= false;
    $GLOBALS['mockTempnamPath'] ??= null;
    $GLOBALS['lastTempnamFile'] ??= null;
    $GLOBALS['lastUnlinkedFile'] ??= null;

    if (! function_exists(__NAMESPACE__.'\tempnam')) {
        function tempnam(string $directory, string $prefix): string|false
        {
            if (! empty($GLOBALS['mockTempnamFalse'])) {
                return false;
            }

            if (isset($GLOBALS['mockTempnamPath']) && is_string($GLOBALS['mockTempnamPath'])) {
                return $GLOBALS['mockTempnamPath'];
            }

            $path = \tempnam($directory, $prefix);
            $GLOBALS['lastTempnamFile'] = $path;

            return $path;
        }
    }

    if (! function_exists(__NAMESPACE__.'\unlink')) {
        /**
         * @param  resource|null  $context
         */
        function unlink(string $filename, $context = null): bool
        {
            $GLOBALS['lastUnlinkedFile'] = $filename;

            return $context !== null ? \unlink($filename, $context) : \unlink($filename);
        }
    }
}

namespace Linkxtr\FilamentQr\Services {
    $GLOBALS['mockImagickLoaded'] ??= null;

    if (! function_exists(__NAMESPACE__.'\extension_loaded')) {
        function extension_loaded(string $extension): bool
        {
            if ($extension === 'imagick' && is_bool($GLOBALS['mockImagickLoaded'])) {
                return $GLOBALS['mockImagickLoaded'];
            }

            return \extension_loaded($extension);
        }
    }
}
