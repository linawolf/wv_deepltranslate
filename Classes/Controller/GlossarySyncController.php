<?php

declare(strict_types=1);

namespace WebVision\WvDeepltranslate\Controller;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use WebVision\WvDeepltranslate\Domain\Repository\GlossaryRepository;
use WebVision\WvDeepltranslate\Exception\InvalidArgumentException;
use WebVision\WvDeepltranslate\Service\DeeplGlossaryService;

class GlossarySyncController
{
    protected DeeplGlossaryService $deeplGlossaryService;

    public function __construct(
        DeeplGlossaryService $deeplGlossaryService
    ) {
        $this->deeplGlossaryService = $deeplGlossaryService;
    }

    /**
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function update(ServerRequestInterface $request): RedirectResponse
    {
        $processingParameters = $request->getQueryParams();

        if (!isset($processingParameters['uid'])) {
            throw new InvalidArgumentException(
                'No ID given for glossary synchronization',
                1676935668643
            );
        }

        $this->deeplGlossaryService->syncGlossaries((int)$processingParameters['uid']);

        $flashMessage = GeneralUtility::makeInstance(
            FlashMessage::class,
            LocalizationUtility::translate(
                'glossary.sync.message',
                'wv_deepltranslate'
            ),
            LocalizationUtility::translate(
                'glossary.sync.title',
                'wv_deepltranslate'
            ),
            FlashMessage::OK,
            true
        );

        GeneralUtility::makeInstance(FlashMessageService::class)
            ->getMessageQueueByIdentifier()
            ->enqueue($flashMessage);

        return new RedirectResponse($processingParameters['returnUrl']);
    }
}
