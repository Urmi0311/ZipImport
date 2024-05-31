<?php
namespace Sigma\ZipCodeImport\Controller\Adminhtml\Export;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

/**
 * Class Index
 * Sigma\ZipCodeImport\Block\Adminhtml\Export
 *
 */
class Index extends Action
{
    /**
     * Factory for creating file write instances.
     *
     * @var \Magento\Framework\Filesystem\File\WriteFactory
     */
    protected $fileFactory;

    /**
     * Connection to the Magento resource.
     *
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;

    /**
     * Logger instance for logging messages.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Constructor.
     *
     * @param Context              $context
     * @param FileFactory          $fileFactory
     * @param ResourceConnection   $resourceConnection
     * @param LoggerInterface      $logger
     */
    public function __construct(
        Context $context,
        FileFactory $fileFactory,
        ResourceConnection $resourceConnection,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->_fileFactory = $fileFactory;
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
    }

    /**
     * Export zip codes to a CSV file.
     *
     * @return \Magento\Framework\App\Response\Http\FileInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        try {
            $fileName = 'zip_codes.csv';
            $connection = $this->resourceConnection->getConnection();
            $tableName = $this->resourceConnection->getTableName('zip_code_import');

            $data = [];
            $data[] = ['city_name', 'zip_code', 'same_day_delivery', 'next_evening_delivery'];

            $query = $connection->select()->from($tableName);
            $results = $connection->fetchAll($query);

            foreach ($results as $row) {
                $data[] = [
                    $row['city_name'],
                    $row['zip_code'],
                    $row['same_day_delivery'],
                    $row['next_evening_delivery']
                ];
            }
            $this->logger->info('Exporting zip codes.', ['data' => $data]);
            $csvString = '';
            foreach ($data as $row) {
                $csvString.= implode(',', $row). "\n";
            }

            return $this->_fileFactory->create(
                $fileName,
                $csvString,
                DirectoryList::VAR_DIR,
                'application/csv'
            );
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);

            $this->messageManager->addError(__('An error occurred while exporting zip codes.'));
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('*/*/');
        }
    }
}
