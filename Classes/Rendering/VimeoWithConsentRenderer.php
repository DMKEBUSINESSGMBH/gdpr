<?php

declare(strict_types=1);

namespace GeorgRinger\Gdpr\Rendering;

use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\Rendering\VimeoRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;

class VimeoWithConsentRenderer extends VimeoRenderer
{
    public const DEFAULT_TEMPLATE = 'EXT:gdpr/Resources/Private/Templates/Rendering/Vimeo.html';

    public function __construct(protected readonly ViewFactoryInterface $viewFactory)
    {
    }

    #[\Override]
    public function getPriority()
    {
        return 2;
    }

    #[\Override]
    public function canRender(FileInterface $file)
    {
        return ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend() && parent::canRender($file);
    }

    #[\Override]
    public function render(FileInterface $file, $width, $height, ?array $options = null)
    {
        $uniqueId = uniqid('', true);
        $htmlCode = parent::render($file, $width, $height, $options);
        $htmlCode = str_replace('<iframe src="', '<iframe style="display:none" id="iframe-'.$uniqueId.'" data-src="', $htmlCode);

        $templatePath = $options['gdpr-vimeo-template'] ?? self::DEFAULT_TEMPLATE;
        $view = $this->viewFactory->create(new ViewFactoryData(
            templatePathAndFilename: GeneralUtility::getFileAbsFileName($templatePath),
        ));
        $view->assignMultiple([
            'width' => $width,
            'height' => $height,
            'uniqueId' => $uniqueId,
            'html' => $htmlCode,
            'file' => $file,
            'options' => $options,
        ]);

        return $view->render();
    }
}
