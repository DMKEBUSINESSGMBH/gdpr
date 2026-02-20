<?php

declare(strict_types=1);

namespace GeorgRinger\Gdpr\Domain\Repository;

use GeorgRinger\Gdpr\Domain\Model\Dto\LogFilter;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

class LogRepository extends BaseRepository
{
    public const LOG_TABLE = 'tx_gdpr_domain_model_log';

    public function filter(LogFilter $filter): array
    {
        $queryBuilder = $this->getQueryBuilder(self::LOG_TABLE);

        $where = [];
        if (!in_array($filter->getTableName(), ['', '0'], true)) {
            $where[] = $queryBuilder->expr()->like('table_name', $queryBuilder->createNamedParameter($filter->getTableName(), Connection::PARAM_STR));
        }

        if (0 !== $filter->getStatus()) {
            $where[] = $queryBuilder->expr()->eq('status', $queryBuilder->createNamedParameter($filter->getStatus(), Connection::PARAM_INT));
        }

        $dateFrom = $filter->getDateFrom();
        if ('' !== $dateFrom && '0' !== $dateFrom) {
            $date = $this->getTimeRestriction($dateFrom);
            if (0 !== $date) {
                $where[] = $queryBuilder->expr()->gte('tstamp', $queryBuilder->createNamedParameter($date, Connection::PARAM_INT));
            }
        }

        $dateTo = $filter->getDateTo();
        if ('' !== $dateTo && '0' !== $dateTo) {
            $date = $this->getTimeRestriction($dateTo);
            if (0 !== $date) {
                $where[] = $queryBuilder->expr()->lte('tstamp', $queryBuilder->createNamedParameter($date, Connection::PARAM_INT));
            }
        }

        $res = $queryBuilder
            ->select('*')
            ->from(self::LOG_TABLE)
            ->setMaxResults($filter->getLimit())
            ->orderBy('tstamp', 'desc');

        if ([] !== $where) {
            $res->where(...$where);
        }

        return $res->executeQuery()->fetchAllAssociative();
    }

    private function getTimeRestriction(string $timeInput): int
    {
        $timeLimit = 0;
        if (MathUtility::canBeInterpretedAsInteger($timeInput)) {
            $timeLimit = GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('date', 'timestamp') - $timeInput;
        } else {
            $timeByFormat = \DateTime::createFromFormat('HH:mm DD-MM-YYYY', $timeInput);
            if ($timeByFormat) {
                $timeLimit = $timeByFormat->getTimestamp();
            } else {
                // try to check strtotime
                $timeFromString = strtotime($timeInput);

                if ($timeFromString) {
                    $timeLimit = $timeFromString;
                }
            }
        }

        return $timeLimit;
    }
}
