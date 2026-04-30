<?php

declare(strict_types=1);

namespace GeorgRinger\RecordTypeSelection\Xclass;

use GeorgRinger\RecordTypeSelection\Backend\RecordGroup;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Clipboard\Clipboard;
use TYPO3\CMS\Backend\Controller\RecordListController;
use TYPO3\CMS\Backend\RecordList\DatabaseRecordList;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\ComponentFactory;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class XclassedRecordListController extends RecordListController
{
    protected function getDocHeaderButtons(ModuleTemplate $view, Clipboard $clipboard, ServerRequestInterface $request, DatabaseRecordList $dbList): void
    {
        parent::getDocHeaderButtons($view, $clipboard, $request, $dbList);

        if (!$this->pageContext->isAccessible()) {
            return;
        }

        $tablesOnPage = $dbList->getTablesOnPage();
        if (count($tablesOnPage) < 2) {
            return;
        }

        $recordGroups = GeneralUtility::makeInstance(RecordGroup::class)->generate($tablesOnPage);

        $componentFactory = GeneralUtility::makeInstance(ComponentFactory::class);

        $dropdownButton = $componentFactory->createDropDownButton()
            ->setLabel('Tables')
            ->setShowLabelText(true)
            ->setShowActiveLabelText(true);

        $dropdownButton->addItem(
            $componentFactory->createDropDownRadio()
                ->setLabel('All tables')
                ->setHref((string)$this->uriBuilder->buildUriFromRoute('web_list', ['id' => $this->pageContext->pageId]))
                ->setActive($this->table === '')
        );

        foreach ($recordGroups as $control) {
            $dropdownButton->addItem($componentFactory->createDropDownDivider());
            $dropdownButton->addItem(
                $componentFactory->createDropDownHeader()->setLabel($control['title'])
            );

            foreach ($control['items'] ?? [] as $itemTable => $item) {
                $dropdownButton->addItem(
                    $componentFactory->createDropDownRadio()
                        ->setLabel($item['label'])
                        ->setIcon($item['icon'])
                        ->setHref((string)$this->uriBuilder->buildUriFromRoute('records', [
                            'id' => $this->pageContext->pageId,
                            'table' => $itemTable,
                        ]))
                        ->setActive($itemTable === $this->table)
                );
            }
        }

        $view->addButtonToButtonBar($dropdownButton, ButtonBar::BUTTON_POSITION_RIGHT, 0);
    }
}
