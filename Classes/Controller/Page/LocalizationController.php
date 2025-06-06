<?php

declare(strict_types=1);

namespace AutoDudes\AiSuite\Controller\Page;

use AutoDudes\AiSuite\Service\SiteService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class LocalizationController extends \TYPO3\CMS\Backend\Controller\Page\LocalizationController
{
    private const ACTION_LOCALIZE_OPEN_AI = 'localizeChatGPT';
    private const ACTION_LOCALIZE_ANTHROPIC = 'localizeAnthropic';
    private const ACTION_LOCALIZE_GOOGLE_TRANSLATE = 'localizeGoogleTranslate';
    private const ACTION_LOCALIZE_DEEPL = 'localizeDeepl';
    private const ACTION_LOCALIZE_AISUITETEXTULTIMATE = 'localizeAiSuiteTextUltimate';
    private const ACTION_COPY_OPEN_AI = 'copyFromLanguageChatGPT';
    private const ACTION_COPY_ANTHROPIC = 'copyFromLanguageAnthropic';
    private const ACTION_COPY_GOOGLE_TRANSLATE = 'copyFromLanguageGoogleTranslate';
    private const ACTION_COPY_DEEPL = 'copyFromLanguageDeepl';
    private const ACTION_COPY_AISUITETEXTULTIMATE = 'copyFromLanguageAiSuiteTextUltimate';

    public function __construct()
    {
        parent::__construct();
    }

    public function getRecordLocalizeSummary(ServerRequestInterface $request): ResponseInterface
    {
        if (ExtensionManagementUtility::isLoaded('container')) {
            $response = parent::getRecordLocalizeSummary($request);
            $payload = json_decode($response->getBody()->getContents(), true);
            $recordLocalizeSummaryModifier = GeneralUtility::makeInstance(\B13\Container\Service\RecordLocalizeSummaryModifier::class);
            $payload = $recordLocalizeSummaryModifier->rebuildPayload($payload);
            return new JsonResponse($payload);
        }
        return parent::getRecordLocalizeSummary($request);
    }

    public function localizeRecords(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();

        if (!isset($params['pageId'], $params['srcLanguageId'], $params['destLanguageId'], $params['action'], $params['uidList'])) {
            return new JsonResponse(null, 400);
        }

        if (
            $params['action'] !== static::ACTION_COPY
            && $params['action'] !== static::ACTION_LOCALIZE
            && $params['action'] !== static::ACTION_LOCALIZE_OPEN_AI
            && $params['action'] !== static::ACTION_LOCALIZE_ANTHROPIC
            && $params['action'] !== static::ACTION_LOCALIZE_GOOGLE_TRANSLATE
            && $params['action'] !== static::ACTION_LOCALIZE_DEEPL
            && $params['action'] !== static::ACTION_LOCALIZE_AISUITETEXTULTIMATE
            && $params['action'] !== static::ACTION_COPY_OPEN_AI
            && $params['action'] !== static::ACTION_COPY_ANTHROPIC
            && $params['action'] !== static::ACTION_COPY_GOOGLE_TRANSLATE
            && $params['action'] !== static::ACTION_COPY_DEEPL
            && $params['action'] !== static::ACTION_COPY_AISUITETEXTULTIMATE
        ) {
            $response = new Response('php://temp', 400, ['Content-Type' => 'application/json; charset=utf-8']);
            $response->getBody()->write('Invalid action "' . $params['action'] . '" called.');
            return $response;
        }

        $params['uidList'] = $this->filterInvalidUids(
            (int)$params['pageId'],
            (int)$params['destLanguageId'],
            $this->getSourceLanguageId($params['srcLanguageId']),
            $params['uidList']
        );

        $this->process($params);

        return new JsonResponse([]);
    }

    /**
     * Processes the localization actions
     *
     * @param array $params
     */
    protected function process($params): void
    {
        $destLanguageId = (int)$params['destLanguageId'];

        $cmd = [
            'tt_content' => [],
        ];

        if (isset($params['uidList']) && is_array($params['uidList'])) {
            foreach ($params['uidList'] as $currentUid) {
                if (
                    $params['action'] === static::ACTION_LOCALIZE
                    || $params['action'] === static::ACTION_LOCALIZE_OPEN_AI
                    || $params['action'] === static::ACTION_LOCALIZE_ANTHROPIC
                    || $params['action'] === static::ACTION_LOCALIZE_GOOGLE_TRANSLATE
                    || $params['action'] === static::ACTION_LOCALIZE_DEEPL
                    || $params['action'] === static::ACTION_LOCALIZE_AISUITETEXTULTIMATE
                ) {
                    $cmd['tt_content'][$currentUid] = [
                        'localize' => $destLanguageId,
                    ];

                    if ($params['action'] === static::ACTION_LOCALIZE_OPEN_AI
                        || $params['action'] === static::ACTION_LOCALIZE_ANTHROPIC
                        || $params['action'] === static::ACTION_LOCALIZE_GOOGLE_TRANSLATE
                        || $params['action'] === static::ACTION_LOCALIZE_DEEPL
                        || $params['action'] === static::ACTION_LOCALIZE_AISUITETEXTULTIMATE
                    ) {
                        $siteService = GeneralUtility::makeInstance(SiteService::class);
                        $cmd['localization'][0]['aiSuite']['translateAi'] = str_replace('localize', '', $params['action']);
                        $cmd['localization'][0]['aiSuite']['srcLangIsoCode'] = $siteService->getIsoCodeByLanguageId((int)$params['srcLanguageId'], (int)$params['pageId']);
                        $cmd['localization'][0]['aiSuite']['destLangIsoCode'] = $siteService->getIsoCodeByLanguageId($destLanguageId, (int)$params['pageId']);
                        $cmd['localization'][0]['aiSuite']['destLangId'] = $destLanguageId;
                        $cmd['localization'][0]['aiSuite']['srcLangId'] = (int)$params['srcLanguageId'];
                        $cmd['localization'][0]['aiSuite']['uuid'] = $params['uuid'];
                        $cmd['localization'][0]['aiSuite']['rootPageId'] = $siteService->getSiteRootPageId((int)$params['pageId']);
                    }
                } else {
                    $cmd['tt_content'][$currentUid] = [
                        'copyToLanguage' => $destLanguageId,
                    ];
                    if ($params['action'] === static::ACTION_COPY_OPEN_AI
                        || $params['action'] === static::ACTION_COPY_ANTHROPIC
                        || $params['action'] === static::ACTION_COPY_GOOGLE_TRANSLATE
                        || $params['action'] === static::ACTION_COPY_DEEPL
                        || $params['action'] === static::ACTION_COPY_AISUITETEXTULTIMATE
                    ) {
                        $siteService = GeneralUtility::makeInstance(SiteService::class);
                        $cmd['localization'][0]['aiSuite']['translateAi'] = str_replace('copyFromLanguage', '', $params['action']);;
                        $cmd['localization'][0]['aiSuite']['srcLangIsoCode'] = $siteService->getIsoCodeByLanguageId($params['srcLanguageId'], $params['pageId']);
                        $cmd['localization'][0]['aiSuite']['destLangIsoCode'] = $siteService->getIsoCodeByLanguageId($destLanguageId, $params['pageId']);
                        $cmd['localization'][0]['aiSuite']['destLangId'] = $destLanguageId;
                        $cmd['localization'][0]['aiSuite']['srcLangId'] = (int)$params['srcLanguageId'];
                        $cmd['localization'][0]['aiSuite']['uuid'] = $params['uuid'];
                        $cmd['localization'][0]['aiSuite']['rootPageId'] = $siteService->getSiteRootPageId((int)$params['pageId']);
                    }
                }
            }
        }

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], $cmd);
        $dataHandler->process_cmdmap();
    }

    /**
     * Return source language ID from source language string
     */
    public function getSourceLanguageId(string $srcLanguage): int
    {
        $langParam = explode('-', $srcLanguage);
        if (count($langParam) > 1) {
            return (int)$langParam[1];
        }
        return (int)$langParam[0];
    }
}
