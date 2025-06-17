<?php

declare(strict_types=1);

/**
 * Export table definition class for a table.  Responsible for retrieving the records for a given
 * table definition as well as holding all of the key values for the table.  The key values are used
 * for retrieving the table records based upon all of the foreign key values that have been added to the table
 * to filter on.  Table records are retrieved using the union (SQL OR clause) of all of the key values.
 *
 * Custom tables that have more specific queries can extend this class to override the getRecords method.
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 *
 * @author    Stephen Nielson <snielson@discoverandchange.com
 * @copyright Copyright (c) 2023 OpenEMR Foundation, Inc
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */
namespace OpenEMR\Modules\EhiExporter\TableDefinitions;

use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Modules\EhiExporter\Models\ExportKeyDefinition;

class ExportTableDefinition
{
    public ?string $table;

    public string $selectClause = '*';

    /**
     * @var string[]|int[]
     */
    private array $keyColumnsHashmap = [];

    private bool $hasNewData = false;

    /**
     * @var string[]
     */
    private array $tableColumnNames;

    private array $primaryKeys;

    public function __construct(?string $table = null, array $pks = [])
    {
        $this->table = $table;
        $this->primaryKeys = $pks;
    }

    public function addPrimaryKey(string $key): void
    {
        $this->primaryKeys[] = $key;
    }

    private function createPrimaryKeyHashFromRecord(array &$record): string
    {
        $hash = [];
        foreach ($this->primaryKeys as $primaryKey) {
            $hash[] = $record[$primaryKey];
        }

        return implode('::', $hash);
    }

    public function addKeyValue(ExportKeyDefinition $exportKeyDefinition, int|string $value): void
    {
        $key = $exportKeyDefinition->foreignKeyColumn;
        if ($exportKeyDefinition->isDenormalized && is_string($value)) {
            $valueList = explode($exportKeyDefinition->denormalizedKeySeparator, $value);
            foreach ($valueList as $value) {
                $this->addValueToHashmap($key, $value);
            }
        } else {
            $this->addValueToHashmap($key, $value);
        }
    }

    private function addValueToHashmap(string $key, string|int $value): void
    {
        $hasValue = $this->keyColumnsHashmap[$key][$value] ?? null;
        if (!isset($hasValue)) {
            if (!isset($this->keyColumnsHashmap[$key])) {
                $this->keyColumnsHashmap[$key] = [];
            }

            $this->keyColumnsHashmap[$key][$value] = $value;
            $this->hasNewData = true;
        }
    }

    public function addKeyValueList(ExportKeyDefinition $exportKeyDefinition, array $values): void
    {
        foreach ($values as $value) {
            $this->addKeyValue($exportKeyDefinition, $value);
        }
    }

    public function hasNewData(): bool
    {
        return $this->hasNewData;
    }

    public function setSelectClause(string $clause): void
    {
        $this->selectClause = $clause;
    }

    /**
     * @deprecated
     */
    public function setSelectColumns(array $columns): void
    {
        $select = [];
        foreach ($columns as $column) {
            $select[] = QueryUtils::escapeColumnName($column, [$this->table]);
        }

        $this->selectClause = implode(',', $columns);
    }

    public function getSelectClause(): string
    {
        return $this->selectClause;
    }

    protected function getHashmapForKey($key)
    {
        return $this->keyColumnsHashmap[$key] ?? [];
    }

    /**
     * @return list
     */
    public function getRecords(): array
    {
        $maxIterations = 500; // always have a loop safety in case the loop logic breaks, which is 500 * 25000 = 12,500,000 records
        $iterations = 0;

        $batchSize = 25000;
        // we will just go through each key and grab the records in batches of 25000
        // we'll grab the PK definition, then we'll grab the records in batches, we'll compute a PK hash for each record
        // if the hash is in the PK hashmap then we'll skip it, otherwise we'll add it to the hashmap and add it to the records
        $recordKeyHash = [];
        $resultRecords = [];
        foreach ($this->keyColumnsHashmap as $key => $items) {
            $pos = 0;
            $bindColumnsCount = count($items);
            do {
                $fetchSize = min($batchSize, $bindColumnsCount - $pos);
                // key has already been escaped when we created the table definitions so we can just search against the valid
                // table columns, if it exists we are good to go, otherwise we fail.
                if (!in_array($key, $this->tableColumnNames)) {
                    throw new \RuntimeException("Invalid key column name for table " . $this->table . (': ' . $key));
                }

                $whereClause = sprintf('(%s IN (', $key) . str_repeat('?,', $fetchSize - 1) . "?))";
                $bindColumns = array_slice($items, $pos, $fetchSize);

                $sql = sprintf('SELECT %s FROM %s WHERE %s', $this->getSelectClause(), $this->table, $whereClause);
                $records = QueryUtils::sqlStatementThrowException($sql, $bindColumns, false);
                foreach ($records as $record) {
                    $pkHash = $this->createPrimaryKeyHashFromRecord($record);
                    if (!isset($recordKeyHash[$pkHash])) {
                        $recordKeyHash[$pkHash] = 1; // keep it sane
                        $resultRecords[] = $record;
                    }
                }

                $pos += $fetchSize;
            } while ($pos < $bindColumnsCount && $iterations++ < $maxIterations);
        }

        return $resultRecords;
    }

    public function setHasNewData(bool $newData): void
    {
        $this->hasNewData = false;
    }

    /**
     * @param string[]  $safeColumnNames
     */
    public function setColumnNames(array $safeColumnNames): void
    {
        $this->tableColumnNames = $safeColumnNames;
    }

    /**
     * @return string[]
     */
    public function getColumnNames(): array
    {
        return $this->tableColumnNames;
    }
}
