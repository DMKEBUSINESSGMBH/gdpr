<?php

declare(strict_types=1);

namespace GeorgRinger\Gdpr\Service;

class TableInformation
{
    public static function isTableEnabled(string $table): bool
    {
        return isset($GLOBALS['TCA'][$table])
            && !empty($GLOBALS['TCA'][$table]['ctrl']['gdpr'])
            && is_array($GLOBALS['TCA'][$table]['ctrl']['gdpr'])
            && $GLOBALS['TCA'][$table]['ctrl']['gdpr']['enabled'];
    }

    public static function getAllEnabledTables(): array
    {
        $tables = [];

        foreach (array_keys($GLOBALS['TCA']) as $tableName) {
            if (self::isTableEnabled($tableName)) {
                $tables[] = $tableName;
            }
        }

        return $tables;
    }

    public static function getMetaInformationOfTable(string $table): array
    {
        return $GLOBALS['TCA'][$table];
    }
}
