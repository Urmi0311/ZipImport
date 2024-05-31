<?php

namespace Sigma\ZipCodeImport\Controller\Adminhtml\Import;

use Magento\Framework\Controller\ResultFactory;
use Sigma\ZipCodeImport\Block\Adminhtml\Import\Import;

/**
 * Class Handles index action
 * @var Sigma\ZipCodeImport\Controller\Adminhtml\Import
 *
 */
class Index extends \Sigma\ZipCodeImport\Controller\Adminhtml\Import
{
    /**
     * Import and export Page
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        $resultPage->setActiveMenu('Sigma_ZipCodeImport::zipcodeimport_import');
        $resultPage->getConfig()->getTitle()->prepend(__('Import and Export Zip Code'));
        $resultPage->addContent(
            $resultPage->getLayout()->createBlock(Import::class)
        );

        return $resultPage;
    }
}
