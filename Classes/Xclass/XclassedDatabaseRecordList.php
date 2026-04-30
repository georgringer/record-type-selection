<?php

declare(strict_types=1);

namespace GeorgRinger\RecordTypeSelection\Xclass;

use TYPO3\CMS\Backend\RecordList\DatabaseRecordList;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class XclassedDatabaseRecordList extends DatabaseRecordList
{
    public function getTablesOnPage(): array
    {
        $list = [];
        foreach ($this->getAllowedTables() as $table) {
            if ($this->getCountOfTable($table) > 0) {
                $list[] = $table;
            }
        }
        return $list;
    }

    public function getCountOfTable(string $table): int
    {
        $queryBuilder = $this->getQueryBuilder($table, ['*'], false, 0, 1);
        return (int)$queryBuilder
            ->count('*')
            ->resetOrderBy()
            ->executeQuery()
            ->fetchOne();
    }

    protected function getAllowedTables(): array
    {
        $hideTablesArray = GeneralUtility::trimExplode(',', $this->hideTables);
        $backendUser = $this->getBackendUserAuthentication();

        $tableNames = array_flip($this->tcaSchemaFactory->all()->getNames());
        foreach ($tableNames as $tableName => $_) {
            $hideTable = ($this->tableList && !GeneralUtility::inList($this->tableList, (string)$tableName))
                || !$backendUser->check('tables_select', $tableName);

            if (!$hideTable) {
                $schema = $this->tcaSchemaFactory->get($tableName);
                $hideTable = $schema->hasCapability(TcaSchemaCapability::HideInUi)
                    || in_array($tableName, $hideTablesArray, true)
                    || in_array('*', $hideTablesArray, true);
                $hideTable = (bool)($this->tableTSconfigOverTCA[$tableName . '.']['hideTable'] ?? $hideTable);
            }

            if ($hideTable) {
                unset($tableNames[$tableName]);
            } else {
                $tableNames[$tableName] = $this->tableDisplayOrder[$tableName] ?? [];
            }
        }

        try {
            $orderedTableNames = GeneralUtility::makeInstance(DependencyOrderingService::class)
                ->orderByDependencies($tableNames);
        } catch (\UnexpectedValueException $e) {
            $lang = $this->getLanguageService();
            $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:warning.tableDisplayOrder.message'),
                $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:warning.tableDisplayOrder.title'),
                ContextualFeedbackSeverity::WARNING,
                true
            );
            $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
            $flashMessageService->getMessageQueueByIdentifier()->enqueue($flashMessage);
            $orderedTableNames = $tableNames;
        }

        return array_keys($orderedTableNames);
    }
}
