<?php

declare(strict_types=1);

namespace WebVision\WvDeepltranslate\Service;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use GuzzleHttp\Exception\ClientException;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WebVision\WvDeepltranslate\Client;
use WebVision\WvDeepltranslate\Domain\Repository\GlossaryRepository;
use WebVision\WvDeepltranslate\Utility\DeeplBackendUtility;

class DeeplService
{
    /**
     * Default supported languages
     *
     * @see https://www.deepl.com/de/docs-api/translating-text/#request
     * @var string[]
     */
    public array $apiSupportedLanguages =  [
        'source' => [],
        'target' => [],
    ];

    /**
     * Formality supported languages
     * @var string[]
     */
    public array $formalitySupportedLanguages = [];

    protected GlossaryRepository $glossaryRepository;

    private FrontendInterface $cache;

    private Client $client;

    public function __construct(
        FrontendInterface $cache,
        Client $client,
        GlossaryRepository $glossaryRepository
    ) {
        $this->cache = $cache;
        $this->client = $client;
        $this->glossaryRepository = $glossaryRepository;

        $this->loadSupportedLanguages();
    }

    /**
     * Deepl Api Call for retrieving translation.
     *
     * @return array<int|string, mixed>
     * @throws DBALException
     * @throws Exception
     * @throws SiteNotFoundException
     */
    public function translateRequest(
        string $content,
        string $targetLanguage,
        string $sourceLanguage
    ): array {
        // If the source language is set to Autodetect, no glossary can be detected.
        if ($sourceLanguage === 'auto') {
            $sourceLanguage = '';
            $glossary['glossary_id'] = '';
        } else {
            // TODO make glossary findable by current site
            $glossary = $this->glossaryRepository->getGlossaryBySourceAndTarget(
                $sourceLanguage,
                $targetLanguage,
                DeeplBackendUtility::detectCurrentPage()
            );
        }

        try {
            $response = $this->client->translate($content, $sourceLanguage, $targetLanguage, $glossary['glossary_id']);
        } catch (ClientException $e) {
            $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                $e->getMessage(),
                '',
                FlashMessage::INFO
            );
            GeneralUtility::makeInstance(FlashMessageService::class)
                ->getMessageQueueByIdentifier()
                ->addMessage($flashMessage);

            return [];
        }

        return json_decode($response->getBody()->getContents(), true);
    }

    private function loadSupportedLanguages(): void
    {
        $cacheIdentifier = 'wv-deepl-supported-languages-target';
        if (($supportedTargetLanguages = $this->cache->get($cacheIdentifier)) === false) {
            $supportedTargetLanguages = $this->loadSupportedLanguagesFromAPI();

            $this->cache->set($cacheIdentifier, $supportedTargetLanguages, [], 86400);
        }

        foreach ($supportedTargetLanguages as $supportedLanguage) {
            $this->apiSupportedLanguages['target'][] = $supportedLanguage['language'];
            if ($supportedLanguage['supports_formality'] === true) {
                $this->formalitySupportedLanguages[] = $supportedLanguage['language'];
            }
        }

        $cacheIdentifier = 'wv-deepl-supported-languages-source';

        if (($supportedSourceLanguages = $this->cache->get($cacheIdentifier)) === false) {
            $supportedSourceLanguages = $this->loadSupportedLanguagesFromAPI('source');

            $this->cache->set($cacheIdentifier, $supportedSourceLanguages, [], 86400);
        }

        foreach ($supportedSourceLanguages as $supportedLanguage) {
            $this->apiSupportedLanguages['source'][] = $supportedLanguage['language'];
        }
    }

    private function loadSupportedLanguagesFromAPI(string $type = 'target'): array
    {
        try {
            $response = $this->client->getSupportedTargetLanguage($type);
        } catch (ClientException $e) {
            return [];
        }

        return json_decode($response->getBody()->getContents(), true);
    }
}
