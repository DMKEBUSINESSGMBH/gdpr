<?php

declare(strict_types=1);

namespace GeorgRinger\Gdpr\EventListener;

use GeorgRinger\Gdpr\Domain\Model\Dto\Table;
use GeorgRinger\Gdpr\Log\LogManager;
use GeorgRinger\Gdpr\Service\Randomization;
use GeorgRinger\Gdpr\Service\TableInformation;
use Psr\Http\Message\ResponseFactoryInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\ModifyButtonBarEvent;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;

final class ModifyButtonBar
{
    protected $tableName = '';

    public function __invoke(ModifyButtonBarEvent $event): void
    {
        if (!$this->isButtonVisible()) {
            return;
        }

        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);

        $randomizeStatus = (int) ($GLOBALS['TYPO3_REQUEST']->getQueryParams()['randomize'] ?? null);
        if (1 === $randomizeStatus) {
            $button = $event->getButtonBar()->makeLinkButton();
            $button->setIcon($iconFactory->getIcon('actions-synchronize', IconSize::SMALL));
            $button->setTitle('My custom docHeader button');
            $button->setClasses('t3js-modal-trigger');
            $button->setHref($this->getCurrentRouteWithRandomize(1));
            $button->setDataAttributes([
                'toggle' => 'tooltip',
                'severity' => 'error',
                'title' => 'Randomize record',
                'content' => 'Should this record be really randomized? Content will be gone forever!',
                'button-ok-text' => 'Randomize',
            ]);

            $buttons = $event->getButtons();
            $buttons[ButtonBar::BUTTON_POSITION_LEFT][4][] = $button;
            $event->setButtons($buttons);
        }
    }

    protected function getCurrentRouteWithRandomize(int $randomize): string
    {
        return (string) GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute(
            $GLOBALS['TYPO3_REQUEST']->getAttribute('routing')->getRoute()->getPath(),
            ['randomize' => $randomize]
        );
    }

    /**
     * Checks if the popup button should be displayed. Returns false if not.
     * Otherwise returns true.
     *
     * @return bool
     */
    protected function isButtonVisible()
    {
        $visible = false;
        $contentUid = $this->getContentUid();

        if (null !== $contentUid && $GLOBALS['BE_USER']->isAdmin()) {
            $visible = true;
        }

        if ($visible && 1 === (int) ($GLOBALS['TYPO3_REQUEST']->getQueryParams()['randomize'] ?? null)) {
            $randomizationService = GeneralUtility::makeInstance(Randomization::class, $this->tableName);
            $randomizationService->generateDataForTable();
            $newValues = $randomizationService->generateDataForTable();
            $tableInformation = Table::getInstance($this->tableName);
            $newValues[$tableInformation->getGdprRandomizedField()] = 1;
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->tableName);
            $connection->update(
                $this->tableName,
                $newValues,
                [
                    'uid' => $contentUid,
                ]
            );
            $logger = GeneralUtility::makeInstance(LogManager::class);
            $logger->log($this->tableName, $contentUid, LogManager::STATUS_RANDOMIZE);
            $url = $this->getCurrentRouteWithRandomize(2);

            $response = GeneralUtility::makeInstance(ResponseFactoryInterface::class)
                ->createResponse(303)
                ->withAddedHeader('location', $url);
            throw new PropagateResponseException($response);
        }

        return $visible;
    }

    /**
     * Returns the uid of the currently edited content element in backend.
     *
     * @return int|null content element uid
     */
    protected function getContentUid(): ?int
    {
        $editGetParameters = $this->getEditGetParameters();
        if (!is_array($editGetParameters) || [] === $editGetParameters) {
            return null;
        }

        $contentUid = current(array_keys($editGetParameters));
        if ('edit' !== $editGetParameters[$contentUid]) {
            return null;
        }

        return (int) $contentUid;
    }

    /**
     * Returns the get parameters.
     *
     * @return array|null
     */
    protected function getEditGetParameters()
    {
        $editGetParam = $GLOBALS['TYPO3_REQUEST']->getParsedBody()['edit'] ?? $GLOBALS['TYPO3_REQUEST']->getQueryParams()['edit'] ?? null;
        if (empty($editGetParam)) {
            return null;
        }

        $firstKey = array_keys($editGetParam);
        $tableName = $firstKey[0];
        if (TableInformation::isTableEnabled($tableName)) {
            $this->tableName = $tableName;

            return $editGetParam[$tableName] ?? null;
        }

        return null;
    }
}
