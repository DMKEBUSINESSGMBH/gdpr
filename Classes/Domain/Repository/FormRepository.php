<?php

declare(strict_types=1);

namespace GeorgRinger\Gdpr\Domain\Repository;

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FormRepository extends BaseRepository
{
    protected UriBuilder $uriBuilder;

    protected Registry $registry;

    protected FlexFormService $flexFormService;

    public const REGISTRY_NAMESPACE = 'gdpr_form';

    public const LOG_COUNT_PREVIEW = 5;

    public function __construct()
    {
        $this->uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $this->registry = GeneralUtility::makeInstance(Registry::class);
        $this->flexFormService = GeneralUtility::makeInstance(FlexFormService::class);
    }

    public function getAllForms(): array
    {
        $data = [];

        if (ExtensionManagementUtility::isLoaded('form')) {
            $data['ext-form'] = $this->getFormForms();
        }

        if (ExtensionManagementUtility::isLoaded('powermail')) {
            $data['ext-powermail'] = $this->getPowermailForms();
        }

        if (ExtensionManagementUtility::isLoaded('formhandler')) {
            $data['ext-formhandler'] = $this->getFormhandlerForms();
        }

        return $data;
    }

    public function setStatus(string $type, int $id, bool $status): void
    {
        $this->registry->set(self::REGISTRY_NAMESPACE, $this->getRegistryKey($type, $id), $status);
    }

    protected function getPowermailForms()
    {
        $queryBuilder = $this->getQueryBuilder('tt_content');
        $rows = $queryBuilder
            ->select('*')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('CType', $queryBuilder->createNamedParameter('list', Connection::PARAM_STR)),
                $queryBuilder->expr()->eq('list_type', $queryBuilder->createNamedParameter('powermail_pi1', Connection::PARAM_STR))
            )
            ->executeQuery()
            ->fetchAllAssociative();

        $logTable = 'tx_powermail_domain_model_mail';

        foreach ($rows as $key => $row) {
            $queryBuilder = $this->getQueryBuilder($logTable);
            if (!empty($row['pi_flexform'])) {
                $settings = $this->flexFormService->convertFlexFormContentToArray($row['pi_flexform']);
                $formId = (int) $settings['settings']['flexform']['main']['form'];

                $count = $queryBuilder
                    ->count('*')
                    ->from($logTable)
                    ->where($queryBuilder->expr()->eq('form', $queryBuilder->createNamedParameter($formId, Connection::PARAM_INT)))
                    ->executeQuery()
                    ->fetchOne();

                $finalPreviewRows = [];
                if ($count > 0) {
                    $previewRows = $queryBuilder
                        ->select('*')
                        ->from($logTable)
                        ->where(
                            $queryBuilder->expr()->eq('form', $queryBuilder->createNamedParameter($formId, Connection::PARAM_INT))
                        )
                        ->setMaxResults(self::LOG_COUNT_PREVIEW)->orderBy('uid', 'desc')->executeQuery()->fetchAllAssociative();

                    foreach ($previewRows as $previewRow) {
                        $finalPreviewRows[] = [
                            'uid' => $previewRow['uid'],
                            'tstamp' => $previewRow['tstamp'],
                            'senderName' => $previewRow['sender_name'],
                            'senderEmail' => $previewRow['sender_mail'],
                            'subject' => $previewRow['subject'],
                            'receiverEmail' => $previewRow['receiver_mail'],
                        ];
                    }
                }

                $rows[$key]['_records'] = [
                    'formIdentifier' => $formId,
                    'totalCount' => $count,
                    'previewRows' => $finalPreviewRows,
                ];
            }
        }

        return $this->enhanceRows('powermail', $rows);
    }

    protected function getFormhandlerForms()
    {
        $queryBuilder = $this->getQueryBuilder('tt_content');
        $rows = $queryBuilder
            ->select('*')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->and(
                        $queryBuilder->expr()->eq('CType', $queryBuilder->createNamedParameter('list', Connection::PARAM_STR)),
                        $queryBuilder->expr()->eq('list_type', $queryBuilder->createNamedParameter('formhandler_pi1', Connection::PARAM_STR))
                    ),
                    $queryBuilder->expr()->and(
                        $queryBuilder->expr()->eq('CType', $queryBuilder->createNamedParameter('formhandler_pi1', Connection::PARAM_STR))
                    )
                )
            )
            ->executeQuery()
            ->fetchAllAssociative();

        $logTable = 'tx_formhandler_log';

        foreach ($rows as $key => $row) {
            $queryBuilder = $this->getQueryBuilder($logTable);

            $count = $queryBuilder
                ->count('*')
                ->from($logTable)
                ->where($queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($row['pid'], Connection::PARAM_INT)))
                ->executeQuery()
                ->fetchOne();

            $finalPreviewRows = [];
            if ($count > 0) {
                $previewRows = $queryBuilder
                    ->select('*')
                    ->from($logTable)
                    ->where(
                        $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($row['pid'], Connection::PARAM_INT))
                    )
                    ->setMaxResults(self::LOG_COUNT_PREVIEW)->orderBy('uid', 'desc')->executeQuery()->fetchAllAssociative();

                foreach ($previewRows as $previewRow) {
                    $data = unserialize($previewRow['params'], ['allowed_classes' => false]);
                    $finalPreviewRows[] = [
                        'uid' => $previewRow['uid'],
                        'tstamp' => $previewRow['tstamp'],
                        'senderName' => $data['name'],
                        'senderEmail' => $previewRow['email'],
                    ];
                }
            }

            $rows[$key]['_records'] = [
                'formIdentifier' => $row['pid'],
                'totalCount' => $count,
                'previewRows' => $finalPreviewRows,
            ];
        }

        return $this->enhanceRows('formhandler', $rows);
    }

    protected function getFormForms()
    {
        $queryBuilder = $this->getQueryBuilder('tt_content');
        $rows = $queryBuilder
            ->select('*')
            ->from('tt_content')
            ->where($queryBuilder->expr()->eq('CType', $queryBuilder->createNamedParameter('form_formframework', Connection::PARAM_STR)))
            ->executeQuery()
            ->fetchAllAssociative();

        return $this->enhanceRows('form', $rows);
    }

    protected function enhanceRows(string $type, array $rows): array
    {
        foreach ($rows as $key => $row) {
            $status = $this->registry->get(self::REGISTRY_NAMESPACE, $this->getRegistryKey($type, $row['uid']), false);
            $rows[$key]['_meta'] = [
                'type' => $type,
                'isValidated' => $status,
                'links' => [
                    'editContentElement' => $this->createEditUri('tt_content', $row['uid']),
                ],
                'page' => BackendUtility::getRecord('pages', $row['pid']),
                'path' => BackendUtility::getRecordPath($row['pid'], $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW), 1000),
            ];
        }

        return $rows;
    }

    protected function getRegistryKey(string $type, int $id): string
    {
        return sprintf('%s-%s', $type, $id);
    }

    protected function createEditUri(string $table, int $id): string
    {
        $urlParameters = [
            'edit' => [
                $table => [
                    $id => 'edit',
                ],
            ],
            'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI'),
        ];

        return (string) $this->uriBuilder->buildUriFromRoute('record_edit', $urlParameters);
    }

    /**
     * Returns the current BE user.
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
