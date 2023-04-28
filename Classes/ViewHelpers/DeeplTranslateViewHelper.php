<?php

declare(strict_types=1);

namespace WebVision\WvDeepltranslate\ViewHelpers;

use TYPO3\CMS\Backend\View\PageLayoutContext;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use WebVision\WvDeepltranslate\Utility\DeeplBackendUtility;

/**
 * @deprecated will be removed in v4
 */
class DeeplTranslateViewHelper extends AbstractViewHelper
{
    public function initializeArguments()
    {
        $this->registerArgument(
            'context',
            PageLayoutContext::class,
            'Layout context',
            true
        );
    }

    public function render(): array
    {
        $options = [];
        /** @var PageLayoutContext $context */
        $context = $this->arguments['context'];

        $languageMatch = [];

        foreach ($context->getSiteLanguages() as $siteLanguage) {
            if (
                $siteLanguage->getLanguageId() != -1
                && $siteLanguage->getLanguageId() != 0
            ) {
                if (!DeeplBackendUtility::checkCanBeTranslated($context->getPageId(), $siteLanguage->getLanguageId())) {
                    continue;
                }
                $languageMatch[$siteLanguage->getTitle()] = $siteLanguage->getLanguageId();
            }
        }

        if (count($languageMatch) === 0) {
            return $options;
        }
        foreach ($context->getNewLanguageOptions() as $key => $possibleLanguage) {
            if ($key === 0) {
                continue;
            }
            if (!array_key_exists($possibleLanguage, $languageMatch)) {
                continue;
            }
            $parameters = [
                'justLocalized' => 'pages:' . $context->getPageId() . ':' . $languageMatch[$possibleLanguage],
                'returnUrl' => $GLOBALS['TYPO3_REQUEST']->getAttribute('normalizedParams')->getRequestUri(),
            ];
            $redirectUrl = DeeplBackendUtility::buildBackendRoute('record_edit', $parameters);
            $params = [];
            $params['redirect'] = $redirectUrl;
            $params['cmd']['pages'][$context->getPageId()]['localize'] = $languageMatch[$possibleLanguage];
            $params['cmd']['localization']['custom']['mode'] = 'deepl';
            $targetUrl = DeeplBackendUtility::buildBackendRoute('tce_db', $params);

            $options[$targetUrl] = $possibleLanguage;
        }

        return $options;
    }
}
