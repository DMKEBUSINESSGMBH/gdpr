<?php

if (!isset($GLOBALS['TYPO3_CONF_VARS']['DB']['additionalQueryRestrictions'][GeorgRinger\Gdpr\Database\Query\Restriction\GdprRestriction::class])) {
    $GLOBALS['TYPO3_CONF_VARS']['DB']['additionalQueryRestrictions'][GeorgRinger\Gdpr\Database\Query\Restriction\GdprRestriction::class] = [];
}

$extConfiguration = GeorgRinger\Gdpr\Domain\Model\Dto\ExtensionConfiguration::getInstance();
if ($extConfiguration->getOverloadMediaRenderer()) {
    $rendererRegistry = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(TYPO3\CMS\Core\Resource\Rendering\RendererRegistry::class);
    $rendererRegistry->registerRendererClass(GeorgRinger\Gdpr\Rendering\YoutubeWithConsentRenderer::class);
    $rendererRegistry->registerRendererClass(GeorgRinger\Gdpr\Rendering\VimeoWithConsentRenderer::class);
}

$iconRegistry = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(TYPO3\CMS\Core\Imaging\IconRegistry::class);
$icons = [
    'ext-gdpr-form-overview' => 'form-overview.svg',
];
