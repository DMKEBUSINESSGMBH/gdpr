<?php

return [
    'site_GdprTxgdprm1' => [
        'parent' => 'site',
        'access' => 'user',
        'labels' => 'LLL:EXT:gdpr/Resources/Private/Language/locallang_modadministration.xlf',
        'extensionName' => 'Gdpr',
        'controllerActions' => [
            GeorgRinger\Gdpr\Controller\AdministrationController::class => [
                'index',
                'help',
                'search',
                'delete',
                'disable',
                'reenable',
                'randomize',
                'moduleNotEnabled',
                'log',
                'configuration',
                'formOverview',
                'formStatusUpdate',
            ],
        ],
    ],
];
