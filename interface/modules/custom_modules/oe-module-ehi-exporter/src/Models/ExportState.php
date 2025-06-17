<?php

declare(strict_types=1);

/**
 * Represents the state of an export operation holding all of the working data that is needed
 * to process the export.  Including the current queue of table definitions to export, the xml meta table
 * information as well as the xml concrete table information.
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 *
 * @author    Stephen Nielson <snielson@discoverandchange.com
 * @copyright Copyright (c) 2023 OpenEMR Foundation, Inc
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */
namespace OpenEMR\Modules\EhiExporter\Models;

use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Modules\EhiExporter\TableDefinitions\ExportFormsGroupsEncounterTableDefinition;
use OpenEMR\Modules\EhiExporter\TableDefinitions\ExportClinicalNotesFormTableDefinition;
use OpenEMR\Modules\EhiExporter\TableDefinitions\ExportEsignatureTableDefinition;
use OpenEMR\Modules\EhiExporter\Services\ExportKeyDefinitionFilterer;
use OpenEMR\Modules\EhiExporter\TableDefinitions\ExportOnsiteMailTableDefinition;
use OpenEMR\Modules\EhiExporter\TableDefinitions\ExportOnsiteMessagesTableDefinition;
use OpenEMR\Modules\EhiExporter\TableDefinitions\ExportOpenEmrPostCalendarEventsTableDefinition;
use OpenEMR\Modules\EhiExporter\Services\ExportTableDataFilterer;
use OpenEMR\Modules\EhiExporter\Models\ExportTableResult;
use OpenEMR\Modules\EhiExporter\Models;
use OpenEMR\Modules\EhiExporter\Models\ExportResult;
use OpenEMR\Modules\EhiExporter\Models\ExportKeyDefinition;
use OpenEMR\Modules\EhiExporter\TableDefinitions\ExportTableDefinition;
use OpenEMR\Modules\EhiExporter\Models\EhiExportJobTask;
use OpenEMR\Modules\EhiExporter\TableDefinitions\ExportTrackAnythingFormTableDefinition;

class ExportState
{
    public \SimpleXMLElement $rootNode;

    private \SplQueue $queue;

    private Models\ExportResult $exportResult;

    private array $tableDefinitionsMap = [];

    private SystemLogger $systemLogger;

    // we use this to make sure if we are scheduled to hit an item again
    private array $inQueueList = [];

    private ExportTableDataFilterer $exportTableDataFilterer;

    /**
     * @var string the temp directory to use for this export
     */
    private string $tempDir;

    private \SimpleXMLElement $metaNode;

    private ExportKeyDefinitionFilterer $exportKeyDefinitionFilterer;

    private EhiExportJobTask $ehiExportJobTask;

    public function __construct(SystemLogger $systemLogger, \SimpleXMLElement $tableNode, \SimpleXMLElement $metaNode, EhiExportJobTask $ehiExportJobTask)
    {
        $this->rootNode = $tableNode;
        $this->metaNode = $metaNode;
        $this->queue = new \SplQueue();
        $this->exportResult = new Models\ExportResult();
        $this->exportTableDataFilterer = new ExportTableDataFilterer();
        $this->exportKeyDefinitionFilterer = new ExportKeyDefinitionFilterer();
        $this->ehiExportJobTask = $ehiExportJobTask;

        $this->systemLogger = $systemLogger;
    }

    public function getTempSysDir(): string
    {
        if (!isset($this->tempDir)) {
            $this->tempDir = tempnam(sys_get_temp_dir(), 'ehi-export-');
            if (file_exists($this->tempDir)) {
                unlink($this->tempDir);
            }

            mkdir($this->tempDir);
            if (!is_dir($this->tempDir)) {
                throw new \RuntimeException("Failed to make temporary directory for export in temp directory");
            }
        }

        return $this->tempDir;
    }

    public function getJobTask()
    {
        return $this->ehiExportJobTask;
    }

    public function addExportResultTable(string $tableName, int $recordCount): void
    {
        $exportTableResult = new ExportTableResult();
        $exportTableResult->tableName = $tableName;
        $exportTableResult->count = $recordCount;
        $this->exportResult->exportedTables[$tableName] = $exportTableResult;
        $this->systemLogger->debug("Adding export result table ", ['table' => $tableName, 'count' => $recordCount]);
    }

    public function getExportResult()
    {
        return $this->exportResult;
    }

    public function xmlXPath(string $xpath): array|false|null
    {
        return $this->rootNode->xpath($xpath);
    }

    public function xmlMetaXPath(string $xpath): array|false|null
    {
        return $this->metaNode->xpath($xpath);
    }

    public function getTableDefinitionForTable(string $tableName): ?ExportTableDefinition
    {
        if (isset($this->tableDefinitionsMap[$tableName])) {
            return $this->tableDefinitionsMap[$tableName];
        }

        return null;
    }

    public function getNextTableDefinitionToProcess(): ExportTableDefinition
    {
        $item = $this->queue->dequeue();
        if ($item instanceof ExportTableDefinition) {
            $this->systemLogger->debug("Retrieving next table definition from queue", ['table' => $item->table, 'hasMoreData' => $item->hasNewData()]);
            if (isset($this->inQueueList[$item->table])) {
                unset($this->inQueueList[$item->table]);
            }

            return $item;
        }

        throw new \RuntimeException("Invalid item in queue");
    }

    public function hasTableDefinitions(): bool
    {
        return !$this->queue->isEmpty();
    }

    public function addTableDefinition(\OpenEMR\Modules\EhiExporter\TableDefinitions\ExportTableDefinition $exportTableDefinition): void
    {
        // should exist already, but double check
        if (!isset($this->tableDefinitionsMap[$exportTableDefinition->table])) {
            $this->tableDefinitionsMap[$exportTableDefinition->table] = $exportTableDefinition;
        }

        if (!isset($this->inQueueList[$exportTableDefinition->table])) {
            $this->queue->enqueue($exportTableDefinition);
            $this->inQueueList[$exportTableDefinition->table] = $exportTableDefinition;
            $this->systemLogger->debug("QUEUE: Adding table definition to queue", ['table' => $exportTableDefinition->table]);
        } else {
            $this->systemLogger->debug("QUEUE: Table already exists in queue", ['table' => $exportTableDefinition->table]);
        }
    }

    public function getKeyDataForTable(ExportTableDefinition $exportTableDefinition): array
    {
        $keyData = [
            'tables' => []
            ,'keys' => []
        ];
        $elements = $this->xmlXPath("//table[@name='" . $exportTableDefinition->table . "']/column");
        if ($elements !== false) {
            foreach ($elements as $element) {
                $localColumnName = (string)($element->attributes()['name'] ?? null);
                if ($element->count() > 0) {
                    foreach ($element->children() as $child) {
                        $foreignTableName = (string)($child->attributes()['table'] ?? null);
                        $foreignColumnName = (string)($child->attributes()['column'] ?? null);
                        $keyType = $child->getName();
                        if ($foreignTableName !== '' && $foreignTableName !== '0' && ($foreignColumnName !== '' && $foreignColumnName !== '0')) {
                            if (!isset($this->tableDefinitionsMap[$foreignTableName])) {
                                // TODO: @adunsulag is there a better location higher up the chain to do this
                                // or would it be cleaner to have a NOOP table definition that we can use for this?
                                if (!$this->existsTable($foreignTableName)) {
                                    // we are skipping any tables that don't exist due to the fact that they may not be installed
                                    // such as an optional form.
                                    continue;
                                } else {
                                    $foreignTableDefinition = $this->createTableDefinition($foreignTableName);
                                }
                            } else {
                                $foreignTableDefinition = $this->tableDefinitionsMap[$foreignTableName];
                            }

                            $keyData['tables'][$foreignTableName] = $foreignTableDefinition;
                            $key = new ExportKeyDefinition();
                            $key->foreignKeyTable = $foreignTableName;
                            $key->foreignKeyColumn = $foreignColumnName;
                            $key->localTable = $exportTableDefinition->table;
                            $key->localColumn = $localColumnName;
                            $key->keyType = $keyType;
                            if ($this->exportKeyDefinitionFilterer->hasMultipleKeysForColumn($key)) {
                                $keys = $this->exportKeyDefinitionFilterer->filterMultipleKeys($key);
                                foreach ($keys as $key) {
                                    $keyData['keys'][] = $key;
                                }
                            } else {
                                $key = $this->exportKeyDefinitionFilterer->filterKey($key);
                                $keyData['keys'][] = $key;
                            }
                        }
                    }
                }
            }
        }

        // for any hard-coded denormalized tables we need to handlethose here.
        if ($this->hasDenormalizedKeys($exportTableDefinition)) {
            $keys = $this->getDenormalizedKeys($exportTableDefinition);
            foreach ($keys as $key) {
                $foreignTableName = $key->foreignKeyTable;
                $foreignTableDefinition = $this->getTableDefinitionForTable($foreignTableName) ?? $this->createTableDefinition($foreignTableName);
                $keyData['tables'][$foreignTableName] = $foreignTableDefinition;
                $keyData['keys'][] = $key;
            }
        }

        return $keyData;
    }

    private function hasDenormalizedKeys(\OpenEMR\Modules\EhiExporter\TableDefinitions\ExportTableDefinition $exportTableDefinition): ?bool
    {
        if ($exportTableDefinition->table === 'patient_data' || $exportTableDefinition->table === 'patient_history') {
            return true;
        }
        return null;
    }

    private function getDenormalizedKeys(\OpenEMR\Modules\EhiExporter\TableDefinitions\ExportTableDefinition $exportTableDefinition): array
    {
        // these columns are denormalized data and have the ids separated by a pipe (|)
        if ($exportTableDefinition->table === 'patient_data' || $exportTableDefinition->table == 'patient_history') {
            $care_team_provider = new ExportKeyDefinition();
            $care_team_provider->localTable = $exportTableDefinition->table;
            $care_team_provider->localColumn = "care_team_provider";
            $care_team_provider->foreignKeyColumn = "id";
            $care_team_provider->foreignKeyTable = "users";
            $care_team_provider->isDenormalized = true;
            $care_team_provider->denormalizedKeySeparator = "|";

            $care_team_facility = new ExportKeyDefinition();
            $care_team_facility->localTable = $exportTableDefinition->table;
            $care_team_facility->localColumn = "care_team_facility";
            $care_team_facility->foreignKeyColumn = "id";
            $care_team_facility->foreignKeyTable = "facility";
            $care_team_provider->isDenormalized = true;
            $care_team_provider->denormalizedKeySeparator = "|";
            return [$care_team_provider, $care_team_facility];
        }

        return [];
    }

    public function createTableDefinition(string $tableName)
    {
        // need to make sure we sanitize this
        $safeTableName = QueryUtils::escapeTableName($tableName);
        // we are going to do our safe escaping here so we don't have to do it in the rest of the code.
        $tableDef = $this->exportTableDefininitionFactory($safeTableName);
        $primaryKeys = $this->xmlXPath("//table[@name='" . $safeTableName . "']/primaryKey");
        $pkBySequence = [];
        foreach ($primaryKeys as $primaryKey) {
            $columnName = (string)($primaryKey->attributes()['column']) ?? "";
            $sequenceNo = (int)($primaryKey->attributes()['sequenceNumberInPK'] ?? 0);
            $pkBySequence[$sequenceNo] = $columnName;
        }

        foreach ($pkBySequence as $pk) {
            // since we add the sequence by integer, it will be in order and we can add the primary keys here so we create our hashes properly.
            $tableDef->addPrimaryKey($pk);
        }

        // this will be used to make sure we don't have any sql injection attacks
        $safeColumnNames = QueryUtils::listTableFields($safeTableName);
        $tableDef->setColumnNames($safeColumnNames);
        $this->exportTableDataFilterer->generateSelectQueryForTableFromMetadata($tableDef, $this->metaNode);
        $this->tableDefinitionsMap[$safeTableName] = $tableDef;
        return $tableDef;
    }

    private function exportTableDefininitionFactory(string $tableName): \OpenEMR\Modules\EhiExporter\TableDefinitions\ExportOnsiteMessagesTableDefinition|\OpenEMR\Modules\EhiExporter\TableDefinitions\ExportOnsiteMailTableDefinition|\OpenEMR\Modules\EhiExporter\TableDefinitions\ExportEsignatureTableDefinition|\OpenEMR\Modules\EhiExporter\TableDefinitions\ExportOpenEmrPostCalendarEventsTableDefinition|\OpenEMR\Modules\EhiExporter\TableDefinitions\ExportClinicalNotesFormTableDefinition|\OpenEMR\Modules\EhiExporter\TableDefinitions\ExportFormsGroupsEncounterTableDefinition|\OpenEMR\Modules\EhiExporter\TableDefinitions\ExportTrackAnythingFormTableDefinition|\OpenEMR\Modules\EhiExporter\TableDefinitions\ExportTableDefinition
    {
        // for specific tables that we need to do special handling with
        if ($tableName === ExportOnsiteMessagesTableDefinition::TABLE_NAME) {
            return new ExportOnsiteMessagesTableDefinition($tableName);
        } elseif ($tableName === ExportOnsiteMailTableDefinition::TABLE_NAME) {
            return new ExportOnsiteMailTableDefinition($tableName);
        } elseif ($tableName === ExportEsignatureTableDefinition::TABLE_NAME) {
            return new ExportEsignatureTableDefinition($tableName);
        } elseif ($tableName === ExportOpenEmrPostCalendarEventsTableDefinition::TABLE_NAME) {
            return new ExportOpenEmrPostCalendarEventsTableDefinition($tableName);
        } elseif ($tableName === ExportClinicalNotesFormTableDefinition::TABLE_NAME) {
            return new ExportClinicalNotesFormTableDefinition($tableName);
        } elseif ($tableName === ExportFormsGroupsEncounterTableDefinition::TABLE_NAME) {
            return new ExportFormsGroupsEncounterTableDefinition($tableName);
        } elseif ($tableName === ExportTrackAnythingFormTableDefinition::TABLE_NAME) {
            return new ExportTrackAnythingFormTableDefinition($tableName);
        }

        return new \OpenEMR\Modules\EhiExporter\TableDefinitions\ExportTableDefinition($tableName);
    }

    private function existsTable(string $foreignTableName)
    {
        return QueryUtils::existsTable($foreignTableName);
    }
}
