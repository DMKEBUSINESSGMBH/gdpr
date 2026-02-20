<?php

declare(strict_types=1);

namespace GeorgRinger\Gdpr\EventListener;

use GeorgRinger\Gdpr\Domain\Model\Dto\Table;
use GeorgRinger\Gdpr\Service\TableInformation;
use TYPO3\CMS\Core\Imaging\Event\ModifyRecordOverlayIconIdentifierEvent;

final class ModifyRecordOverlayIconIdentifier
{
    public function __invoke(ModifyRecordOverlayIconIdentifierEvent $event): void
    {
        if (TableInformation::isTableEnabled($event->getTable())) {
            $table = Table::getInstance($event->getTable());
            if ($table->randomizationEnabled() && ($event->getRow()[$table->getGdprRandomizedField()] ?? false)) {
                $event->setOverlayIconIdentifier('overlay-locked');
            }
        }
    }
}
