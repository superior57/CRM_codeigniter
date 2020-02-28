<?php

defined('BASEPATH') or exit('No direct script access allowed');

abstract class App_import
{
    /**
     * Codeigniter Instance
     * @var object
     */
    protected $ci;

    /**
     * Stores the import guidelines
     * @var array
     */
    protected $importGuidelines = [];

    /**
     * Text used for sample data tables and CSV
     * @var string
     */
    protected $sampleDataText = 'Sample Data';

    /**
     * Total imported leads
     * @var integer
     */
    protected $totalImported = 0;

    /**
     * App temp folder location
     * @var string
     */
    protected $appTmpFolder = TEMP_FOLDER;

    /**
     * After the uploaded file is moved to the $appTmpFolder, we will store the full file path here
     * for further usage
     * @var string
     */
    protected $tmpFileStoragePath;

    /**
     * Temporary file location from $_FILES
     * Used when intializing the import
     * @var string
     */
    protected $temporaryFileFromFormLocation;

    /**
     * This is actually the temporary dir in the $appTempFolder used when moving the file into $appTempFOlder
     * @var string
     */
    protected $tmpDir;

    /**
     * Uploaded file name
     * @var string
     */
    protected $filename;

    /**
     * The actual .csv file rows
     * @var array
     */
    protected $rows;

    /**
     * Total rows
     * Total count from $rows
     * @var mixed
     */
    protected $totalRows = null;

    /**
     * When the total rows passes this warning number will show a warning to split the import process
     * @var integer
     */
    private $warningOnTotalRows = 500;

    /**
     * Indicating does this import/upload is simulation
     * @var boolean
     */
    protected $isSimulation = false;

    /**
     * Total rows to show when simulating data
     * For example if user have 2500 rows in the .csv file in the simulate HTML table will be shown only $maxSimulationRows
     * @var integer
     */
    protected $maxSimulationRows = 100;

    /**
     * This is tha actual simulation data that will be shown the preview simulation tabe
     * @var array
     */
    protected $simulationData = [];

    /**
     * Database fields that will be used for import
     * @var array
     */
    protected $databaseFields = [];

    /**
     * Custom fields that will be used for import
     * @var array
     */
    protected $customFields = [];

    public function __construct()
    {
        $this->ci = &get_instance();

        $this->setDefaultImportGuidelinesInfo();
    }

    /**
     * This method must be implemented on the child import class
     * This method will perform all the import actions and checks
     * @return mixed
     */
    abstract public function perform();

    /**
     * In some cases there will be some errors that we need to catch, after we catch the errors, we will redirect the user to this URL
     * This method is required and must be implemented in the child class
     * @return string
     */
    abstract protected function failureRedirectURL();

    /**
     * Format column/field name for table heading/csv
     * @param  string $field the actual field name
     * @return string
     */
    public function formatFieldNameForHeading($field)
    {
        return str_replace('_', ' ', ucfirst($field));
    }

    /**
     * Sets database fields
     * @param Object
     */
    public function setDatabaseFields($fields)
    {
        $this->databaseFields = $fields;

        return $this;
    }

    /**
     * Get database fields
     * @return array
     */
    public function getDatabaseFields()
    {
        return $this->databaseFields;
    }

    /**
     * Get importable database fields
     * @return array
     */
    protected function getImportableDatabaseFields()
    {
        if (!property_exists($this, 'notImportableFields')) {
            return $this->databaseFields;
        }

        $fields = [];

        foreach ($this->databaseFields as $field) {
            if (in_array($field, $this->notImportableFields)) {
                continue;
            }
            $fields[] = $field;
        }

        return $fields;
    }

    /**
     * Set custom fields that will be used for import
     * @param object $fields
     */
    public function setCustomFields($fields)
    {
        $this->customFields = $fields;

        return $this;
    }

    /**
     * Get custom fields
     * @return array
     */
    public function getCustomFields()
    {
        return $this->customFields;
    }

    /**
     * Set simulation
     * @param boolean $bool
     */
    public function setSimulation($bool)
    {
        $this->isSimulation = (bool) $bool;

        return $this;
    }

    /**
     * Check whether the request is simulation
     * @return boolean
     */
    public function isSimulation()
    {
        return (bool) $this->isSimulation;
    }

    /**
     * Get all stored simulation data that will be shown in table preview for simulation
     * @return array
     */
    public function getSimulationData()
    {
        return array_values($this->simulationData);
    }

    /**
     * Set the rows from the .csv file
     * @param array $rows
     */
    public function setRows($rows)
    {
        $this->rows = $rows;

        return $this;
    }

    /**
     * Get the rows stored from the .csv file
     * @return [type] [description]
     */
    public function getRows()
    {
        return $this->rows;
    }

    /**
     * Get total rows
     * @return mixed
     */
    public function totalRows()
    {
        return $this->totalRows;
    }

    /**
     * Sets temporary file location from the form ($_FILES)
     * @param string $location
     */
    public function setTemporaryFileLocation($location)
    {
        $this->temporaryFileFromFormLocation = $location;

        return $this;
    }

    /**
     * Get temporary file location
     * @return mixed
     */
    public function getTemporaryFileLocation()
    {
        return $this->temporaryFileFromFormLocation;
    }

    /**
     * Sets filename from the form ($_FILES)
     * @param string $name
     */
    public function setFilename($name)
    {
        $this->filename = $name;

        return $this;
    }

    /**
     * Get filename from the form ($_FILES)
     * @return mixed
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Increment the total imported number
     * @return object
     */
    public function incrementImported()
    {
        $this->totalImported++;

        return $this;
    }

    /**
     * Get total imported
     * @return mixed
     */
    public function totalImported()
    {
        return $this->totalImported;
    }

    /**
     * Get not importable fields
     * Child class should define property e.q. protected $notImportableFields = ['name'];
     * @return array
     */
    public function getNotImportableFields()
    {
        return property_exists($this, 'notImportableFields') ? $this->notImportableFields : [];
    }

    /**
     * Checks and show HTML warning for max_input_vars based on total rows
     * @return mixed
     */
    public function maxInputVarsWarningHtml()
    {
        $max_input = ini_get('max_input_vars');
        $totalRows = $this->totalRows;

        if (($max_input > 0 && !is_null($totalRows) && $totalRows >= $max_input)) {
            return "
            <div class=\"alert alert-warning\">
                Your hosting provider has PHP config <b>max_input_vars</b> set to $max_input.<br/>
                Ask your hosting provider to increase the <b>max_input_vars</b> config to $totalRows or higher in order to import the number of data you are trying to import otherwise try splitting the import .csv file rows and try to import less rows.
              </div>";
        }

        return '';
    }

    /**
     * Get HTML form for download sample .csv file
     * @return string x
     */
    public function downloadSampleFormHtml()
    {
        $form = '';
        $form .= form_open($this->ci->uri->uri_string());
        $form .= form_hidden('download_sample', 'true');
        $form .= '<button type="submit" class="btn btn-success">Download Sample</button>';
        $form .= '<hr />';
        $form .= form_close();

        return $form;
    }

    /**
     * General info for simulation data
     * @return string
     */
    public function simulationDataInfo()
    {
        return ' <h4 class="bold">Simulation Data <small class="text-info">Max ' . $this->maxSimulationRows . ' rows are shown</small></h4>
              <p class="bold">If you are satisfied with the results upload the file again and click import.</p>';
    }

    public function addImportGuidelinesInfo($text, $isImportant = false)
    {
        $this->importGuidelines[] = [
            'text'         => $text,
            'is_important' => $isImportant,
        ];
    }

    public function importGuidelinesInfoHtml()
    {
        $html = '<ul>';
        foreach (array_reverse($this->importGuidelines) as $key => $info) {
            $num = $key + 1;
            $html .= '<li class="' . ($info['is_important'] ? 'text-danger' : '') . '">' . $num . '. ' . $info['text'];
        }
        $html .= '</ul>';

        return $html;
    }

    /**
     * Download sample .csv file
     * @return mixed
     */
    public function downloadSample()
    {
        $totalSampleFields = 0;
        $dbFieldKeys       = [];

        header('Pragma: public');
        header('Expires: 0');
        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename="sample_import_file.csv";');
        header('Content-Transfer-Encoding: binary');

        foreach ($this->getImportableDatabaseFields() as $field) {
            echo '"' . $this->formatFieldNameForHeading($field) . '",';
            $dbFieldKeys[$totalSampleFields] = $field;
            $totalSampleFields++;
        }

        foreach ($this->getCustomFields() as $field) {
            echo '"' . $field['name'] . '",';
            $totalSampleFields++;
        }

        echo "\n";

        $totalSampleRows = 1;

        for ($row = 0; $row < $totalSampleRows; $row++) {
            for ($f = 0; $f < $totalSampleFields; $f++) {
                $sampleDataText = $this->getTableRowDataText(isset($dbFieldKeys[$f]) ? $dbFieldKeys[$f] :  null);

                echo '"' . $sampleDataText . '",';
            }

            // Is not last in for loop
            if ($row < $totalSampleRows - 1) {
                echo "\n";
            }
        }

        echo "\n";
        exit;
    }

    /**
     * Create sample table for sample data and simulation table results
     * @param  boolean $simulation where the table data should be taken from simultion data
     * @return string
     */
    public function createSampleTableHtml($simulation = false)
    {
        $totalFields = 0;
        $allFields   = [];
        $dbFieldKeys = [];

        $table = '<div class="table-responsive no-dt">';
        $table .= '<table class="table table-hover table-bordered">';
        $table .= '<thead>';
        $table .= '<tr>';

        foreach ($this->getImportableDatabaseFields() as $key => $field) {
            array_push($allFields, $field);
            $dbFieldKeys[$key] = $field;

            $table .= '<th class="bold database_field_' . $field . '">';
            if (in_array($field, $this->getRequiredFields())) {
                $table .= '<span class="text-danger">*</span> ';
            }
            $table .= $this->formatFieldNameForHeading($field);

            // Only for database fields
            if (method_exists($this, 'afterSampleTableHeadingText')) {
                $table .= $this->afterSampleTableHeadingText($field);
            }
            $table .= '</th>';
        }

        foreach ($this->getCustomFields() as $field) {
            array_push($allFields, $field['name']);
            $table .= '<th class="bold custom_field_' . $field['id'] . '">';
            $table .= $field['name'];
            $table .= '</th>';
        }

        $totalFields = count($allFields);

        $table .= '</tr>';
        $table .= '</thead>';
        $table .= '<tbody>';

        if ($simulation == false) {
            for ($i = 0; $i < 1; $i++) {
                $table .= '<tr>';
                for ($x = 0; $x < $totalFields; $x++) {
                    $sampleDataText = $this->getTableRowDataText(isset($dbFieldKeys[$x]) ? $dbFieldKeys[$x] :  null);

                    $table .= '<td>' . $sampleDataText . '</td>';
                }
                $table .= '</tr>';
            }
        } else {
            $simulationData = $this->getSimulationData();

            $totalSimulationRows = count($simulationData);
            for ($i = 0; $i < $totalSimulationRows; $i++) {
                $table .= '<tr>';
                for ($x = 0; $x < $totalFields; $x++) {
                    if (!isset($simulationData[$i][$allFields[$x]])) {
                        $table .= '<td>/</td>';
                    } else {
                        $table .= '<td>' . $simulationData[$i][$allFields[$x]] . '</td>';
                    }
                }
                $table .= '</tr>';
            }
        }
        $table .= '</tbody>';
        $table .= '</table>';
        $table .= '</div>';

        return $table;
    }

    /**
     * Get required import fields
     * Child class should define property e.q. protected $requiredFields = ['name'];
     * @return array
     */
    public function getRequiredFields()
    {
        return property_exists($this, 'requiredFields') ? $this->requiredFields : [];
    }

    /**
     * This is the main function that will initialize the import before parsing all data
     * **** IMPORTANT ***** The child class must call this method inside the perform method
     * @return object
     */
    protected function initialize()
    {
        if (empty($this->temporaryFileFromFormLocation)) {
            set_alert('warning', _l('import_upload_failed'));
            redirect($this->failureRedirectURL());
        }

        $tmpDir = $this->appTmpFolder . '/' . time() . uniqid() . '/';

        $this->maybeCreateDir($this->appTmpFolder);

        $this->maybeCreateDir($tmpDir);

        $this->tmpDir = $tmpDir;

        $this->moveUploadedFile();

        $this->readFileRows();

        return $this;
    }

    /**
     * Format field name
     * @param  string $fieldName field name, if passed will check for custom row data formatter in child class
     * @return string
     */
    private function getTableRowDataText($fieldName = null)
    {
        if (!$fieldName) {
            return $this->sampleDataText;
        }

        $customFormatSampleDataMethod = $fieldName . '_formatSampleData';
        // Only for database fields
        if (method_exists($this, $customFormatSampleDataMethod)) {
            return$this->{$customFormatSampleDataMethod}();
        }

        return $this->sampleDataText;
    }

    /**
     * Create dir if the dir do not exists
     * @param  string $path the dir/path where to create
     * @return boolena
     */
    private function maybeCreateDir($path)
    {
        if (!file_exists($path)) {
            return mkdir($path, 0755);
        }

        return false;
    }

    /**
     * Move the uploaded file into the corresponding temporary directory
     * @return boolean
     */
    private function moveUploadedFile()
    {
        $newFilePath = $this->tmpDir . $this->filename;

        if (move_uploaded_file($this->temporaryFileFromFormLocation, $newFilePath)) {
            $this->tmpFileStoragePath = $newFilePath;

            return true;
        }

        return false;
    }

    /**
     * Read the rows and store them into $rows
     * @return mixed
     */
    protected function readFileRows()
    {
        $fd   = fopen($this->tmpFileStoragePath, 'r');
        $rows = [];
        while ($row = fgetcsv($fd)) {
            $rows[] = $row;
        }

        fclose($fd);

        $this->totalRows = count($rows);

        if ($this->totalRows <= 1) {
            set_alert('warning', 'Not enought rows for importing');
            redirect($this->failureRedirectURL());
        }

        unset($rows[0]);

        $this->setRows($rows);

        if ($this->isSimulation() && $this->totalRows > $this->warningOnTotalRows) {
            $warningMsg = 'Recommended splitting the CSV file into smaller files. Our recomendation is ' . $this->warningOnTotalRows . ' row, your CSV file has ' . $this->totalRows;

            set_alert('warning', $warningMsg);
        }

        return $this;
    }

    /**
     * Some users enter in the .csv rows data e.q. NULL or null
     * To prevent storing this as string in database we shoul make the value empty
     * This is useful too when checking for required fields
     * @param  string $val
     * @return mixed
     */
    protected function checkNullValueAddedByUser($val)
    {
        if ($val === 'NULL' || $val === 'null') {
            $val = '';
        }

        return $val;
    }

    /**
     * Trim the values before inserting
     * @param  array $insert
     * @return array
     */
    protected function trimInsertValues($insert)
    {
        foreach ($insert as $key => $val) {
            $insert[$key] = !is_null($val) ? trim($val) : $val;
        }

        return $insert;
    }

    /**
     * Function responsible to store the import custom fields
     * @param  mixed $rel_id        the ID e.q. lead_id or item_id
     * @param  array $row           the actual row from the loop in the child class
     * @param  mixed &$fieldNumber  field number
     * @param  mixed $rowNumber     the row number, used for simulation data
     * @param  string $customFieldTo where this custom fields belongs
     * @return null
     */
    protected function handleCustomFieldsInsert($rel_id, $row, &$fieldNumber, $rowNumber, $customFieldTo)
    {
        foreach ($this->getCustomFields() as $field) {
            if ($this->isSimulation()) {
                $this->simulationData[$rowNumber][$field['name']] = $row[$fieldNumber];
                $fieldNumber++;

                continue;
            }

            if ($row[$fieldNumber] != '' && $row[$fieldNumber] !== 'NULL' && $row[$fieldNumber] !== 'null') {
                $customFieldData = [
                                        'relid'   => $rel_id,
                                        'fieldid' => $field['id'],
                                        'value'   => trim($row[$fieldNumber]),
                                        'fieldto' => $customFieldTo,
                                    ];
                $this->ci->db->insert(db_prefix().'customfieldsvalues', $customFieldData);
            }
            $fieldNumber++;
        }
    }

    private function setDefaultImportGuidelinesInfo()
    {
        $this->addImportGuidelinesInfo('If the column <b>you are trying to import is date make sure that is formatted in format Y-m-d (' . date('Y-m-d') . ').</b>');

        $this->addImportGuidelinesInfo('Your CSV data should be in the format below. The first line of your CSV file should be the column headers as in the table example. Also make sure that your file is <b>UTF-8</b> to avoid unnecessary <b>encoding problems</b>.');
    }

    /**
     * Clear the temporary dir if exists while moved the uploaded file
     */
    public function __destruct()
    {
        if (!is_null($this->tmpDir) && is_dir($this->tmpDir)) {
            @delete_dir($this->tmpDir);
        }
    }
}
