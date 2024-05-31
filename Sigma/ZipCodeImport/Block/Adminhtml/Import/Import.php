<?php

namespace Sigma\ZipCodeImport\Block\Adminhtml\Import;

/**
 * Class Import
 * Sigma\ZipCodeImport\Block\Adminhtml\Import
 */
class Import extends \Magento\Backend\Block\Widget
{
    /**
     * @var string
     */
    protected $_template = 'import.phtml';

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     */
    public function __construct(\Magento\Backend\Block\Template\Context $context, array $data = [])
    {
        parent::__construct($context, $data);
        $this->setUseContainer(true);
    }
}
