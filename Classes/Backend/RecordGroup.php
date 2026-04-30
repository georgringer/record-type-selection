<?php

declare(strict_types=1);

namespace GeorgRinger\RecordTypeSelection\Backend;

use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class RecordGroup
{
    public function __construct(
        protected readonly IconFactory $iconFactory,
        protected readonly TcaSchemaFactory $tcaSchemaFactory,
    ) {}

    /**
     * Returns tables grouped by TCA groupName or extension key.
     *
     * Result structure:
     * [
     *   'groupKey' => [
     *     'title' => 'Group Title',
     *     'items' => [
     *       'table_name' => ['label' => 'Table Label', 'icon' => Icon],
     *     ],
     *   ],
     * ]
     */
    public function generate(array $tableList): array
    {
        $lang = $this->getLanguageService();
        $groupTitles = [
            'backendaccess' => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_misc.xlf:recordgroup.backendaccess'),
            'content' => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_misc.xlf:recordgroup.content'),
            'frontendaccess' => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_misc.xlf:recordgroup.frontendaccess'),
            'system' => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_misc.xlf:system_records'),
        ];
        $coreGroups = array_keys($groupTitles);

        $rows = [];
        foreach ($tableList as $table) {
            $schema = $this->tcaSchemaFactory->get($table);
            $ctrlTitle = $schema->getTitle();
            $nameParts = explode('_', $table);
            $groupName = $schema->getRawConfiguration()['groupName'] ?? null;

            if (!in_array($groupName, $coreGroups, true) || $nameParts[0] === 'tx' || $nameParts[0] === 'tt') {
                $groupName = $groupName ?? $nameParts[1] ?? null;

                if ($groupName !== null) {
                    $_EXTKEY = '';
                    if (str_starts_with($ctrlTitle, 'LLL:EXT:')) {
                        $_EXTKEY = substr($ctrlTitle, 8, (int)strpos($ctrlTitle, '/', 8) - 8);
                    } elseif (ExtensionManagementUtility::isLoaded($groupName)) {
                        $_EXTKEY = $groupName;
                    }

                    if ($_EXTKEY !== '' && !isset($groupTitles[$groupName])) {
                        $package = GeneralUtility::makeInstance(PackageManager::class)->getPackage($_EXTKEY);
                        $groupTitle = $lang->sL('LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_db.xlf:extension.title');
                        $groupTitles[$groupName] = $groupTitle ?: ($package->getPackageMetaData()->getTitle() ?: ucwords($_EXTKEY));
                    }
                } else {
                    $groupName = 'system';
                }
            }

            $rows[$groupName]['title'] = $rows[$groupName]['title'] ?? $groupTitles[$groupName] ?? $nameParts[1] ?? $lang->sL($ctrlTitle);
            $rows[$groupName]['items'][$table] = [
                'label' => $lang->sL($ctrlTitle),
                'icon' => $this->iconFactory->getIconForRecord($table, [], IconSize::SMALL),
            ];
        }

        return $rows;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
