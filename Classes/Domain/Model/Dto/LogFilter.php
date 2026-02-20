<?php

declare(strict_types=1);

namespace GeorgRinger\Gdpr\Domain\Model\Dto;

use TYPO3\CMS\Core\Utility\MathUtility;

class LogFilter
{
    public const DEFAULT_LIMIT = 50;

    public const MAX_LIMIT = 200;

    /** @var string */
    protected $tableName = '';

    /** @var int */
    protected $status = 0;

    /** @var string */
    protected $dateFrom = '';

    /** @var string */
    protected $dateTo = '';

    /** @var int */
    protected $limit = self::DEFAULT_LIMIT;

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function setTableName(string $tableName): void
    {
        $this->tableName = $tableName;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @param int $status
     */
    public function setStatus($status = 0): void
    {
        $this->status = (int) $status;
    }

    public function getDateFrom(): string
    {
        return $this->dateFrom;
    }

    public function setDateFrom(string $dateFrom): void
    {
        $this->dateFrom = $dateFrom;
    }

    public function getDateTo(): string
    {
        return $this->dateTo;
    }

    public function setDateTo(string $dateTo): void
    {
        $this->dateTo = $dateTo;
    }

    public function getLimit(): int
    {
        return MathUtility::forceIntegerInRange($this->limit, 10, self::MAX_LIMIT, self::DEFAULT_LIMIT);
    }

    /**
     * @param int $limit
     */
    public function setLimit($limit): void
    {
        $this->limit = (int) $limit;
    }
}
