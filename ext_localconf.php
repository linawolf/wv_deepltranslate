<?php

if (!defined('TYPO3_MODE')) {
    die();
}

(static function (): void {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
        '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:wv_deepltranslate/Configuration/TsConfig/Page/pagetsconfig.tsconfig">'
    );

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['wvDeepltranslate_updateGlossary']
    = \WebVision\WvDeepltranslate\Upgrades\GlossaryUpgradeWizard::class;

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['Backend\Template\Components\ButtonBar']['getButtonsHook']['wv_deepltranslate'] =
        \WebVision\WvDeepltranslate\Hooks\ButtonBarHook::class . '->getButtons';
    //allowLanguageSynchronizationHook manipulates l10n_state
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][]
        = \WebVision\WvDeepltranslate\Hooks\AllowLanguageSynchronizationHook::class;

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][]
        = \WebVision\WvDeepltranslate\Hooks\Glossary\UpdatedGlossaryEntryTermHook::class;

    //hook for translate content
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processTranslateToClass']['deepl']
        = \WebVision\WvDeepltranslate\Hooks\TranslateHook::class;
    //hook to checkModifyAccessList for editors
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['checkModifyAccessList']['deepl']
        = \WebVision\WvDeepltranslate\Hooks\TCEmainHook::class;

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-all']['deepl-1675946132'] =
        \WebVision\WvDeepltranslate\Hooks\DeeplPreviewFlagGeneratePageHook::class . '->renderDeeplPreviewFlag';

    //xclass localizationcontroller for localizeRecords() and process() action
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Backend\Controller\Page\LocalizationController::class] = [
        'className' => \WebVision\WvDeepltranslate\Override\LocalizationController::class,
    ];

    //xclass databaserecordlist for rendering custom checkboxes to toggle deepl selection in recordlist
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList::class] = [
        'className' => \WebVision\WvDeepltranslate\Override\DatabaseRecordList::class,
    ];

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Recordlist\Controller\RecordListController::class] = [
        'className' => \WebVision\WvDeepltranslate\Override\DeeplRecordListController::class,
    ];

    if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('container')) {
        //xclass CommandMapPostProcessingHook for translating contents within containers
        if (class_exists(\B13\Container\Hooks\Datahandler\CommandMapPostProcessingHook::class)) {
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\B13\Container\Hooks\Datahandler\CommandMapPostProcessingHook::class] = [
                'className' => \WebVision\WvDeepltranslate\Override\CommandMapPostProcessingHook::class,
            ];
        }
    }

    if (
        TYPO3_MODE === 'BE'
        && \WebVision\WvDeepltranslate\Utility\DeeplBackendUtility::isDeeplApiKeySet()
    ) {
        // overriding localization.js
        $pageRenderer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class);
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/WvDeepltranslate/Localization');
    }

    //add caching for DeepL API-supported Languages
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['wvdeepltranslate']
        ??= [];
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['wvdeepltranslate']['backend']
        ??= \TYPO3\CMS\Core\Cache\Backend\SimpleFileBackend::class;
})();
