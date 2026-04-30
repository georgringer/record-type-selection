<?php

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Backend\RecordList\DatabaseRecordList::class] = [
    'className' => \GeorgRinger\RecordTypeSelection\Xclass\XclassedDatabaseRecordList::class,
];
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Backend\Controller\RecordListController::class] = [
    'className' => \GeorgRinger\RecordTypeSelection\Xclass\XclassedRecordListController::class,
];
