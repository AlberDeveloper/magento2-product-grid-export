<?php

namespace JustBetter\ProductGridExport\Model\Export;

use Magento\Framework\Exception\LocalizedException;

class ConvertToCsv extends \Magento\Ui\Model\Export\ConvertToCsv
{
    /**
     * Returns CSV file
     *
     * @return array
     * @throws LocalizedException
     */
    public function getCsvFile()
    {
        $component = $this->filter->getComponent();

        $name = md5(microtime());
        $file = 'export/'. $component->getName() . $name . '.csv';

        $this->filter->applySelectionOnTargetProvider();
        $dataProvider = $component->getContext()->getDataProvider();
        $fields = $this->metadataProvider->getFields($component);

        $this->directory->create('export');
        $stream = $this->directory->openFile($file, 'w+');
        $stream->lock();
        $stream->writeCsv($this->metadataProvider->getHeaders($component));
        $page = 1;

        $searchResult = $dataProvider->getSearchResult();
        $searchCriteria = $searchResult
            ->setCurPage($page)
            ->setPageSize($this->pageSize);
        $totalCount = (int) $searchResult->getSize();
        while ($totalCount > 0) {
            $items = $searchResult->getItems();
            foreach ($items as $item) {
                $this->metadataProvider->convertDate($item, $component->getName());
                $stream->writeCsv($this->metadataProvider->getRowData($item, $fields, []));
            }
            $searchCriteria->setCurPage(++$page);
            $totalCount = $totalCount - $this->pageSize;
        }

        $stream->unlock();
        $stream->close();

        return [
            'type' => 'filename',
            'value' => $file,
            'rm' => true  // can delete file after use
        ];
    }
}
