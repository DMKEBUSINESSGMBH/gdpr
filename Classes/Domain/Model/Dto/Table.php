<?php

declare(strict_types=1);

namespace GeorgRinger\Gdpr\Domain\Model\Dto;

use GeorgRinger\Gdpr\Service\TableInformation;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Table
{
    protected string $tableName;

    /** @var string */
    protected $title = '';

    /** @var string */
    protected $titleField = '';

    /** @var string */
    protected $deletedField = '';

    /** @var string */
    protected $titleLabel = '';

    /** @var string */
    protected $gdprRestrictionField = '';

    /** @var string */
    protected $gdprRandomizedField = '';

    /** @var string */
    protected $gdprRandomizedDateField = '';

    /** @var int */
    protected $gdprExpirePeriod = 0;

    /** @var array */
    protected $gdprRandomizeMapping = [];

    public function __construct(string $tableName)
    {
        if (!TableInformation::isTableEnabled($tableName)) {
            throw new \UnexpectedValueException(sprintf('Table "%s" is not enabled for GDPR', $tableName), 1519298518);
        }

        $tcaCtrl = $GLOBALS['TCA'][$tableName]['ctrl'];

        $this->tableName = $tableName;
        $this->title = $tcaCtrl['title'];
        $this->titleField = $tcaCtrl['label'];
        $this->deletedField = $tcaCtrl['delete'] ?? '';
        $this->titleLabel = $GLOBALS['TCA'][$tableName]['columns'][$this->titleField]['label'] ?? '';
        $this->gdprRestrictionField = $tcaCtrl['gdpr']['restriction_field'] ?? '';
        $this->gdprRandomizedField = $tcaCtrl['gdpr']['randomized_field'] ?? '';
        $this->gdprRandomizeMapping = $tcaCtrl['gdpr']['randomize_mapping'] ?? [];
        $this->gdprRandomizedDateField = $tcaCtrl['gdpr']['randomize_datefield'] ?? '';
        $this->gdprExpirePeriod = $tcaCtrl['gdpr']['randomize_expirePeriod'] ?? 365;
    }

    public static function getInstance(string $tableName): self
    {
        return GeneralUtility::makeInstance(self::class, $tableName);
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getTitleField(): string
    {
        return $this->titleField;
    }

    public function getDeletedField(): string
    {
        return $this->deletedField;
    }

    public function getTitleLabel(): string
    {
        return $this->titleLabel;
    }

    public function getGdprRestrictionField(): string
    {
        return $this->gdprRestrictionField;
    }

    public function getGdprRandomizedField(): string
    {
        return $this->gdprRandomizedField;
    }

    public function getGdprRandomizeMapping(): array
    {
        return $this->gdprRandomizeMapping;
    }

    public function getGdprRandomizedDateField(): string
    {
        return $this->gdprRandomizedDateField;
    }

    public function getGdprExpirePeriod(): int
    {
        return $this->gdprExpirePeriod;
    }

    public function randomizationEnabled(): bool
    {
        return !empty($this->gdprRandomizedField) && !empty($this->gdprRandomizeMapping);
    }
}
