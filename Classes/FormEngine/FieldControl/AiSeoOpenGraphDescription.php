<?php

declare(strict_types=1);

namespace AutoDudes\AiSuite\FormEngine\FieldControl;

use AutoDudes\AiSuite\Service\SiteService;
use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class AiSeoOpenGraphDescription extends AbstractNode
{
    public function render(): array
    {
        if(!$GLOBALS['BE_USER']->check('custom_options', 'tx_aisuite_features:enable_metadata_generation')) {
            return [];
        }
        try {
            $siteService = GeneralUtility::makeInstance(SiteService::class);
            $pageUid = (int)$this->data['databaseRow']['uid'];
            if(isset($this->data['databaseRow']['l10n_parent'][0]) && (int)$this->data['databaseRow']['l10n_parent'][0] > 0) {
                $pageUid = (int)$this->data['databaseRow']['l10n_parent'][0];
            }
            $langIsoCode = $siteService->getIsoCodeByLanguageId((int)$this->data['databaseRow']['sys_language_uid'], $pageUid);
        } catch (SiteNotFoundException $e) {
            GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__)->error($e->getMessage());
            return [];
        }
        return [
            'iconIdentifier' => 'actions-document-synchronize',
            'title' => $GLOBALS['LANG']->sL('LLL:EXT:ai_suite/Resources/Private/Language/locallang.xlf:AiSuite.generation.ogDescriptionSuggestions'),
            'linkAttributes' => [
                'id' => 'og_description_generation',
                'class' => 'ai-suite-suggestions-generation-btn',
                'data-id' => $this->data['databaseRow']['uid'],
                'data-page-id' => $this->data['databaseRow']['uid'],
                'data-lang-iso-code' => $langIsoCode,
                'data-table' => $this->data['tableName'],
                'data-field-name' => 'og_description',
                'data-field-label' => 'OgDescription',
            ],
            'javaScriptModules' => [
                JavaScriptModuleInstruction::create('@autodudes/ai-suite/metadata/generate-suggestions.js')
            ],
        ];
    }
}
