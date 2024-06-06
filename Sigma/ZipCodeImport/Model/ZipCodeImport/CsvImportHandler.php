<?php

namespace Sigma\ZipCodeImport\Model\ZipCodeImport;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\File\Csv;
use Magento\Framework\Filesystem;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\App\Filesystem\DirectoryList;

class CsvImportHandler
{
    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var Csv
     */
    protected $csvProcessor;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var DirectoryList
     */
    protected $directoryList;

    /**
     * @var File
     */
    protected $file;

    /**
     * Array to store errors encountered during CSV import.
     *
     * @var array
     */
    protected $errors = [];

    /**
     * CsvImportHandler constructor.
     *
     * @param ResourceConnection    $resourceConnection
     * @param Csv                   $csvProcessor
     * @param Filesystem            $filesystem
     * @param LoggerInterface       $logger
     * @param ScopeConfigInterface  $scopeConfig
     * @param DirectoryList         $directoryList
     * @param File                  $file
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        Csv $csvProcessor,
        Filesystem $filesystem,
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig,
        DirectoryList $directoryList,
        File $file
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->csvProcessor = $csvProcessor;
        $this->filesystem = $filesystem;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->directoryList = $directoryList;
        $this->file = $file;
    }

    /**
     * Imports data from a CSV file.
     *
     * @param array $file The uploaded file data.
     * @throws \Magento\Framework\Exception\LocalizedException If an error occurs during the import process.
     */
    public function importFromCsvFile($file)
    {
        if (!isset($file['tmp_name'])) {
            throw new LocalizedException(__('Invalid file upload attempt.'));
        }

        $csvData = $this->csvProcessor->getData($file['tmp_name']);
        $csvData = $this->prepareData($csvData);

        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('zip_code_import');

        foreach ($csvData as $row) {
            try {
                $this->validateRow($row);
                $sameDayDelivery = $row['same_day_delivery'] === '' ||
                $row['same_day_delivery'] === null ? null :
                    filter_var($row['same_day_delivery'], FILTER_VALIDATE_BOOLEAN);
                $nextEveningDelivery = $row['tomorrow_night_delivery'] === '' ||
                $row['tomorrow_night_delivery'] === null ? null :
                    filter_var($row['tomorrow_night_delivery'], FILTER_VALIDATE_BOOLEAN);

                $existingRecord = $connection->fetchRow(
                    $connection->select()
                        ->from($tableName)
                        ->where('zip_code = ?', $row['zip_code'])
                );

                if ($existingRecord) {
                    $data = [
                        'city_name' => $row['city_name'],
                        'same_day_delivery' => $sameDayDelivery,
                        'tomorrow_night_delivery' => $nextEveningDelivery,
                    ];
                    $where = ['zip_code = ?' => $row['zip_code']];
                    $connection->update($tableName, $data, $where);
                    $this->logger->info('Updated record for zip code: ' . $row['zip_code']);
                } else {
                    $data = [
                        'city_name' => $row['city_name'],
                        'zip_code' => $row['zip_code'],
                        'same_day_delivery' => $sameDayDelivery,
                        'tomorrow_night_delivery' => $nextEveningDelivery,
                    ];
                    $connection->insert($tableName, $data);
                    $this->logger->info('Inserted new record for zip code: ' . $data['zip_code']);
                }
            } catch (LocalizedException $e) {
                $row['error'] = $e->getMessage();
                $this->errors[] = $row;
                $this->logger->error($e->getMessage());
            }
        }

        if (!empty($this->errors)) {
            $this->generateErrorCsv();
            throw new LocalizedException(__('Some Error(s) has been occurred during importing process.'));
        }
    }

    /**
     * Prepares the CSV data by associating each row with its corresponding header keys.
     *
     * @param array $data The CSV data to be prepared.
     * @return array The prepared CSV data.
     */
    protected function prepareData(array $data)
    {
        $keys = array_shift($data);
        foreach ($data as $index => &$row) {
            if (count($keys) != count($row)) {
                $this->logger->error("Row $index has a different number of elements than the header.");
                unset($data[$index]);
                continue;
            }
            $row = array_combine($keys, $row);
        }

        return $data;
    }

    /**
     * Validates a single row of CSV data.
     *
     * @param array $row The CSV row to be validated.
     * @throws \Magento\Framework\Exception\LocalizedException If the row fails validation.
     */
    protected function validateRow(array $row)
    {
        if (!isset($row['zip_code']) || !isset($row['same_day_delivery']) ||
            !isset($row['tomorrow_night_delivery']) || !isset($row['city_name'])) {
            throw new LocalizedException(__('The CSV file must contain the columns: city_name, zip_code,
             same_day_delivery, tomorrow_night_delivery.'));
        }

        if (empty($row['zip_code'])) {
            throw new LocalizedException(__('The zip_code is required.'));
        }

        if (empty($row['city_name'])) {
            throw new LocalizedException(__('The city_name is required.'));
        }

        $isSameDayDeliveryEnabled = $this->scopeConfig->isSetFlag('carriers/samedayshipping/active');
        $isNextEveningDeliveryEnabled = $this->scopeConfig->isSetFlag('carriers/nexteveningdelivery/active');

        if ($isSameDayDeliveryEnabled && ($row['same_day_delivery'] === '' || $row['same_day_delivery'] === null)) {
            throw new LocalizedException(__('Same day field is empty.'));
        }

        if ($isNextEveningDeliveryEnabled && ($row['tomorrow_night_delivery'] === '' ||
                $row['tomorrow_night_delivery'] === null)) {
            throw new LocalizedException(__('Next evening field is empty.'));
        }

        if (!empty($row['same_day_delivery']) && !in_array($row['same_day_delivery'], ['0', '1'], true)) {
            throw new LocalizedException(__('The value for same_day_delivery must be either 0 or 1.'));
        }

        if (!empty($row['tomorrow_night_delivery']) && !in_array($row['tomorrow_night_delivery'], ['0', '1'], true)) {
            throw new LocalizedException(__('The value for tomorrow_night_delivery must be either 0 or 1.'));
        }
    }

    /**
     * Generates an error CSV file containing the errors encountered during CSV import.
     *
     * @return void
     */
    protected function generateErrorCsv()
    {
        $varDir = $this->directoryList->getPath(DirectoryList::VAR_DIR);
        $errorFile = $varDir . '/import_zipcode_errors.csv';

        $this->csvProcessor->saveData($errorFile, array_merge([array_keys($this->errors[0])], $this->errors));
        $this->logger->info('Generated error CSV: ' . $errorFile);
    }
}
