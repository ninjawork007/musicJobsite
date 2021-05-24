<?php

namespace App\Service;

use Google_Service_Sheets_Sheet;
use Google_Service_Sheets_Spreadsheet;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Class GoogleSheetsService
 *
 * @package App\Service
 */
class GoogleSheetsService
{
    /**
     * @var \Google_Client
     */
    private $client;

    /**
     * @var \Google_Service_Sheets|null
     */
    private $sheets = null;

    /**
     * @var Google_Service_Sheets_Spreadsheet
     */
    private $currentBook = null;

    /**
     * @var Google_Service_Sheets_Sheet
     */
    private $currentList = null;

    private $configDir;

    private $initialized = false;

    /**
     * GoogleSheetsService constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->client    = new \Google_Client();
        $this->configDir = $container->getParameter('kernel.root_dir') . DIRECTORY_SEPARATOR . 'google' . DIRECTORY_SEPARATOR;
    }

    /**
     * @throws \Google_Exception
     * @throws \Exception
     * @throws \Google_Exception
     */
    public function initClient()
    {
        if ($this->initialized) {
            return;
        }
        if (!file_exists($this->configDir)) {
            mkdir($this->configDir);
        }
        $this->client->setApplicationName('Vocalizr');
        $this->client->addScope(\Google_Service_Sheets::SPREADSHEETS);
        $this->client->setAuthConfig($this->configDir . 'credentials.json');
        $this->client->setAccessType('offline');
        $this->client->fetchAccessTokenWithAssertion();
        $this->client->setPrompt('select_account consent');
        $this->sheets = new \Google_Service_Sheets($this->client);
    }

    /**
     * @param string      $bookId
     * @param string|null $listId
     *
     * @return $this
     */
    public function openSheet($bookId, $listId = null)
    {
        $book              = $this->sheets->spreadsheets->get($bookId);
        $subSheets         = $book->getSheets();
        $this->currentBook = $book;
        if (!$listId) {
            $this->currentList = $subSheets[0];
        } else {
            foreach ($subSheets as $subSheet) {
                if ($subSheet->getProperties()->getTitle() === $listId) {
                    $this->currentList = $subSheet;
                }
            }
        }
        return $this;
    }

    /**
     * @param int  $rowIndex
     * @param int  $columnStartIndex
     * @param null $length
     *
     * @return \Google_Service_Sheets_ValueRange
     */
    public function getRow($rowIndex, $columnStartIndex = 0, $length = null)
    {
        $rowIndex++;
        $startColumnLetter = $this->getLetterIndex($columnStartIndex);
        $columnCount       = $this->currentList->getProperties()->getGridProperties()->columnCount;
        if ($length < 1) {
            $endColumnLetter = $this->getLetterIndex($columnCount - 1);
        } else {
            $endColumnLetter = $this->getLetterIndex($length - 1);
        }

        $subTable = $this->getRange("$startColumnLetter$rowIndex:$endColumnLetter$rowIndex");

        return $subTable[0];
    }

    /**
     * @param $columnIndex
     * @param int      $rowStartIndex
     * @param int|null $length
     *
     * @return array
     */
    public function getColumn($columnIndex, $rowStartIndex = 0, $length = null)
    {
        $columnData = [];
        $rowStartIndex++;
        $columnLetter = $this->getLetterIndex($columnIndex);
        if ($length !== null) {
            $rowEndIndex = $rowStartIndex + $length;
        } else {
            $rowEndIndex = $this->currentList->getProperties()->getGridProperties()->rowCount;
        }
        $tableData = $this->getRange("$columnLetter$rowStartIndex:$columnLetter$rowEndIndex");
        foreach ($tableData as $row) {
            if (!array_key_exists(0, $row)) {
                $columnData[] = null;
                continue;
            }
            $columnData[] = $row[0];
        }
        return $columnData;
    }

    /**
     * @param int   $columnIndex
     * @param int   $rowStartIndex
     * @param array $columnData
     */
    public function setColumn($columnIndex, $rowStartIndex = 0, $columnData)
    {
        $tableData = [];
        $rowStartIndex++;
        $columnLetter = $this->getLetterIndex($columnIndex);
        $rowEndIndex  = $rowStartIndex + count($columnData) - 1;
        foreach ($columnData as $key => $cellData) {
            $tableData[$key][] = $cellData;
        }
        $this->setRange("$columnLetter$rowStartIndex:$columnLetter$rowEndIndex", $tableData);
    }

    /**
     * @param string $range
     *
     * @return \Google_Service_Sheets_ValueRange
     */
    public function getRange($range)
    {
        return $this->sheets->spreadsheets_values->get($this->currentBook->getSpreadsheetId(), $this->listTitle() . '!' . $range);
    }

    /**
     * @param string $range
     * @param array  $rangeData
     * @param bool   $last
     *
     * @throws \Google_Service_Exception
     */
    public function setRange($range, $rangeData, $last = false)
    {
        try {
            $range = $this->listTitle() . '!' . $range;
            $data  = new \Google_Service_Sheets_ValueRange();

            $data->setRange($range);
            $data->setValues($rangeData);
            $this->sheets->spreadsheets_values->append($this->currentBook->getSpreadsheetId(), $range, $data, [
                'valueInputOption' => 'USER_ENTERED',
            ]);
        } catch (\Google_Service_Exception $exception) {
            $error = $exception->getErrors()[0];
            if ($error['reason'] !== 'rateLimitExceeded' || $last) {
                throw $exception;
            }

            printf("{$error['message']}\nWait for 10 minutes");
            sleep(10 * 60);
            $this->client->fetchAccessTokenWithAssertion();
            $this->setRange($range, $rangeData, true);
        }
    }

    /**
     * @return string
     */
    private function listTitle()
    {
        return $this->currentList->getProperties()->getTitle();
    }

    /**
     * @param int $intIndex
     *
     * @return string
     */
    private function getLetterIndex($intIndex)
    {
        $intIndex++;
        $alphabet     = range('A', 'Z');
        $lettersCount = count($alphabet);
        $letterIndex  = '';
        do {
            $offset      = ($intIndex - 1) % $lettersCount;
            $letterIndex = $alphabet[$offset] . $letterIndex;
            $intIndex    = ($intIndex - $offset) / $lettersCount;
        } while ($intIndex >= 1);

        return $letterIndex;
    }

    private function indexToCell($columnIndex, $rowIndex)
    {
        if ($columnIndex < 0) {
            $columnIndex = $this->currentList->getProperties()->getGridProperties()->columnCount + $columnIndex;
        }
        if ($rowIndex < 0) {
            $rowIndex = $this->currentList->getProperties()->getGridProperties()->rowCount + $rowIndex;
        }
        return $this->getLetterIndex($columnIndex) . $rowIndex;
    }
}