<?php

namespace Sigma\ZipCodeImport\Controller\Adminhtml\Import;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Message\ManagerInterface;

class DownloadErrorCsv extends \Magento\Backend\App\Action implements HttpGetActionInterface
{
    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $fileFactory;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * Constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     * @param Filesystem $filesystem
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        Filesystem $filesystem,
        ManagerInterface $messageManager
    ) {
        $this->fileFactory = $fileFactory;
        $this->filesystem = $filesystem;
        $this->messageManager = $messageManager;
        parent::__construct($context);
    }

    /**
     * Execute action
     *
     * @return \Magento\Framework\Controller\Result\Redirect|\Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        $fileName = $this->getRequest()->getParam('file');
        $filePath = $this->filesystem->getDirectoryRead(DirectoryList::VAR_DIR)->getAbsolutePath() . $fileName;

        if (!$this->filesystem->getDirectoryRead(DirectoryList::VAR_DIR)->isFile($fileName)) {
            $this->messageManager->addError(__('File does not exist.'));
            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('*/*/');
        }

        return $this->fileFactory->create(
            $fileName,
            [
                'type' => 'filename',
                'value' => $filePath,
                'rm' => false,
            ],
            DirectoryList::VAR_DIR
        );
    }
}
