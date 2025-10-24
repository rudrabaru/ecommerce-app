<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
    // Paths to analyze/refactor
    $rectorConfig->paths([
        __DIR__ . '/app',
        __DIR__ . '/database', // optional if you want migrations/factories analyzed
    ]);

    // Use predefined sets (safe for Laravel 12)
    $rectorConfig->sets([
        SetList::CODE_QUALITY,       // improves readability and code quality
        SetList::CODING_STYLE,       // fixes formatting issues
        SetList::PHP_81,             // upgrade PHP version (if using PHP 8.1)
        // SetList::TYPE_DECLARATION // optional, adds type hints (use carefully)
    ]);

    // Exclude paths/files if necessary
    $rectorConfig->skip([
        __DIR__ . '/app/Providers', // example: skip service providers
    ]);
};
