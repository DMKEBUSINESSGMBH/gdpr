<?php

declare(strict_types=1);

namespace GeorgRinger\Gdpr\Report;

use GeorgRinger\Gdpr\Domain\Repository\RecordRepository;
use GeorgRinger\Gdpr\Service\TableInformation;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Reports\Status as ReportStatus;
use TYPO3\CMS\Reports\StatusProviderInterface;

/**
 * Report for GDPR.
 */
class GdprStatusReport implements StatusProviderInterface
{
    /**
     * Get status information.
     */
    public function getStatus(): array
    {
        return [
            'gdpr' => $this->getStatusOfGdpr(),
        ];
    }

    protected function getStatusOfGdpr(): ReportStatus
    {
        $recordRepository = GeneralUtility::makeInstance(RecordRepository::class);

        $messages = [];
        $status = ContextualFeedbackSeverity::OK;
        foreach (TableInformation::getAllEnabledTables() as $table) {
            $statistic = $recordRepository->getStatisticOfTable($table);
            $countAction = $statistic['restricted'];
            $countNoAction = $statistic['public'];
            $sum = $countAction + $countNoAction;
            if ($countAction > 0) {
                $status = ContextualFeedbackSeverity::WARNING;
                $messages[] = sprintf('In Table "%s" are %s rows total, %s need an action!', $table, $sum, $countAction);
            } else {
                $messages[] = sprintf('In Table "%s" are %s rows total, no action required.', $table, $sum);
            }
        }

        $message = implode('<br>', $messages);

        return GeneralUtility::makeInstance(
            ReportStatus::class,
            'GDPR Handling',
            'Some information regarding GDPR related sensible information:',
            $message,
            $status
        );
    }

    public function getLabel(): string
    {
        return 'GDPR Handling';
    }
}
