<?php

namespace Sigma\ZipCodeImport\Controller\Adminhtml\Import;

use Magento\Framework\Controller\ResultFactory;
use Sigma\ZipCodeImport\Model\ZipCodeImport\CsvImportHandler;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Backend\Model\UrlInterface;

class ImportPost extends \Sigma\ZipCodeImport\Controller\Adminhtml\Import
{
    /**
     * Executes the action to handle zip code import.
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $request = $this->getRequest();
        if ($request->isPost() && !empty($request->getFiles('import_zipcode_file'))) {
            try {
                $importHandler = $this->_objectManager->create(CsvImportHandler::class);
                $importHandler->importFromCsvFile($this->getRequest()->getFiles('import_zipcode_file'));
                $this->messageManager->addSuccess(__('Zip Code import has been successfully completed.'));
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $errorFile = $this->_objectManager->get(DirectoryList::class)
                   ->getPath(DirectoryList::VAR_DIR) . '/import_zipcode_errors.csv';
                $downloadLink = $this->_objectManager->get(UrlInterface::class)
                ->getUrl('zipcodeimport/import/downloadErrorCsv', ['file' => 'import_zipcode_errors.csv']);
                $this->messageManager->addError($e->getMessage() .
                ' <a href="' . $downloadLink . '">Download full report</a>');
            } catch (\Exception $e) {
                $this->messageManager->addError(__($e->getMessage()));
            }
        } else {
            $this->messageManager->addError(__('Invalid file upload attempt'));
        }
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('zipcodeimport/import/index');
        return $resultRedirect;
    }
}
