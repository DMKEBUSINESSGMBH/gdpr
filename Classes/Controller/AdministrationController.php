<?php

declare(strict_types=1);

namespace GeorgRinger\Gdpr\Controller;

use GeorgRinger\Gdpr\Domain\Model\Dto\LogFilter;
use GeorgRinger\Gdpr\Domain\Model\Dto\Search;
use GeorgRinger\Gdpr\Domain\Model\Dto\Table;
use GeorgRinger\Gdpr\Domain\Repository\FormRepository;
use GeorgRinger\Gdpr\Domain\Repository\LogRepository;
use GeorgRinger\Gdpr\Domain\Repository\RecordRepository;
use GeorgRinger\Gdpr\Service\TableInformation;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\DateFormatter;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;

class AdministrationController extends ActionController
{
    /** @var RecordRepository */
    protected $recordRepository;

    protected ModuleTemplate $moduleTemplate;

    public function __construct(private readonly ModuleTemplateFactory $moduleTemplateFactory)
    {
    }

    protected function initializeAction(): void
    {
        $this->recordRepository = GeneralUtility::makeInstance(RecordRepository::class);

        if ('moduleNotEnabled' !== $this->request->getControllerActionName() && 0 === (int) $this->getBackendUser()->user['gdpr_module_enable']) {
            throw new PropagateResponseException($this->redirect('moduleNotEnabled'));
        }
    }

    /**
     * @param \TYPO3Fluid\Fluid\View\ViewInterface $view
     */
    public function initializeView($view): void
    {
        $this->moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->loadJavaScriptModule('@typo3/backend/modal.js');
        $pageRenderer->loadJavaScriptModule('@gdpr/date-picker.js');

        $formatter = new DateFormatter();
        $dateFormat = [];
        $dateFormat[0] = $formatter->convertPhpFormatToLuxon($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] ?? 'd.m.Y');
        $dateFormat[1] = $dateFormat[0] . ' ' . $formatter->convertPhpFormatToLuxon($GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'] ?? 'H:i');
        $pageRenderer->addInlineSetting('DateTimePicker', 'DateFormat', $dateFormat);

        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();

        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $uriBuilder->setRequest($this->request);

        $this->moduleTemplate->assignMultiple([
            't3DateTimeFormat' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'].' '.$GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'],
        ]);

        $buttonList = [
            [
                'action' => 'index',
                'icon' => 'actions-system-list-open',
                'position' => ButtonBar::BUTTON_POSITION_LEFT,
                'group' => 1,
            ],
            [
                'action' => 'search',
                'icon' => 'actions-search',
                'position' => ButtonBar::BUTTON_POSITION_LEFT,
                'group' => 1,
            ],
            [
                'action' => 'formOverview',
                'icon' => 'ext-gdpr-form-overview',
                'position' => ButtonBar::BUTTON_POSITION_LEFT,
                'group' => 1,
            ],
            [
                'action' => 'log',
                'icon' => 'actions-document-open-read-only',
                'position' => ButtonBar::BUTTON_POSITION_LEFT,
                'group' => 2,
            ],
            [
                'action' => 'configuration',
                'icon' => 'actions-system-extension-configure',
                'position' => ButtonBar::BUTTON_POSITION_RIGHT,
                'group' => 1,
            ],
            [
                'action' => 'help',
                'icon' => 'actions-system-help-open',
                'position' => ButtonBar::BUTTON_POSITION_RIGHT,
                'group' => 2,
            ],
        ];

        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);

        foreach ($buttonList as $buttonDefinition) {
            $button = $buttonBar->makeLinkButton()
                ->setIcon($iconFactory->getIcon($buttonDefinition['icon'], IconSize::SMALL))
                ->setTitle($buttonDefinition['icon'])
                ->setHref($uriBuilder
                    ->reset()
                    ->setRequest($this->request)->uriFor($buttonDefinition['action'], [], 'Administration'));
            $buttonBar->addButton($button, $buttonDefinition['position'], $buttonDefinition['group']);
        }
    }

    public function indexAction(): ResponseInterface
    {
        $tables = TableInformation::getAllEnabledTables();

        $collectedRows = [];
        foreach ($tables as $table) {
            $collectedRows[$table]['statistics'] = $this->recordRepository->getStatisticOfTable($table);
            $collectedRows[$table]['rows'] = $this->recordRepository->getRestrictedRows($table);
            $collectedRows[$table]['meta'] = Table::getInstance($table);
        }

        $this->moduleTemplate->assignMultiple([
            'tables' => $tables,
            'restrictedData' => $collectedRows,
        ]);

        return $this->moduleTemplate->renderResponse('Administration/Index');
    }

    public function deleteAction(string $table, int $uid): ResponseInterface
    {
        $this->recordRepository->deleteRecord($table, $uid);
        $this->addFlashMessage('deleted');

        return new ForwardResponse('index');
    }

    public function reenableAction(string $table, int $uid): ResponseInterface
    {
        $this->recordRepository->enableRecord($table, $uid);
        $this->addFlashMessage('reenabled');

        return new ForwardResponse('index');
    }

    public function disableAction(string $table, int $uid): ResponseInterface
    {
        $this->recordRepository->disableRecord($table, $uid);
        $this->addFlashMessage('disabled');

        return new ForwardResponse('index');
    }

    public function randomizeAction(string $table, int $uid): ResponseInterface
    {
        $this->recordRepository->randomizeRecord($table, $uid);
        $this->addFlashMessage(sprintf('The record with id %d from table "%s" has been randomized', $uid, $table));

        return new ForwardResponse('index');
    }

    public function searchAction(?Search $search = null): ResponseInterface
    {
        $searchPerformed = false;
        if (!$search instanceof Search) {
            $search = GeneralUtility::makeInstance(Search::class);
        } else {
            $searchPerformed = true;
        }

        $this->moduleTemplate->assignMultiple([
            'search' => $search,
            'result' => $this->recordRepository->search($search),
            'searchPerformed' => $searchPerformed,
        ]);

        return $this->moduleTemplate->renderResponse('Administration/Search');
    }

    public function logAction(?LogFilter $filter = null): ResponseInterface
    {
        if (!$filter instanceof LogFilter) {
            $filter = GeneralUtility::makeInstance(LogFilter::class);
        }

        $allTableNames = [];
        foreach (TableInformation::getAllEnabledTables() as $tableName) {
            $allTableNames[$tableName] = $tableName;
        }

        $this->moduleTemplate->assignMultiple([
            'allTableNames' => $allTableNames,
            'filter' => $filter,
            'result' => GeneralUtility::makeInstance(LogRepository::class)->filter($filter),
        ]);

        return $this->moduleTemplate->renderResponse('Administration/Log');
    }

    public function formOverviewAction(): ResponseInterface
    {
        $formRepository = GeneralUtility::makeInstance(FormRepository::class);
        $this->moduleTemplate->assignMultiple([
            'forms' => $formRepository->getAllForms(),
            'previewCount' => FormRepository::LOG_COUNT_PREVIEW,
        ]);

        return $this->moduleTemplate->renderResponse('Administration/FormOverview');
    }

    /**
     * @param string $type   type
     * @param int    $formId form
     * @param int    $status status
     */
    public function formStatusUpdateAction(string $type, int $formId, int $status): ResponseInterface
    {
        $formRepository = GeneralUtility::makeInstance(FormRepository::class);
        $formRepository->setStatus($type, $formId, (bool) $status);

        $this->addFlashMessage(sprintf('The form of content element %s has been updated ', $formId));

        return new ForwardResponse('formOverview');
    }

    public function configurationAction(): ResponseInterface
    {
        $allTables = TableInformation::getAllEnabledTables();

        $information = [];
        foreach ($allTables as $tableName) {
            $information[$tableName] = Table::getInstance($tableName);
        }

        $this->moduleTemplate->assignMultiple([
            'tables' => $information,
        ]);

        return $this->moduleTemplate->renderResponse('Administration/Configuration');
    }

    /**
     * View which shows information if current user got no access.
     */
    public function moduleNotEnabledAction(): ResponseInterface
    {
        return $this->moduleTemplate->renderResponse('Administration/ModuleNotEnabled');
    }

    public function helpAction(): ResponseInterface
    {
        return $this->moduleTemplate->renderResponse('Administration/Help');
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
