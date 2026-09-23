<?php

namespace App\Database;

use Illuminate\Database\Schema\SQLiteBuilder as BaseSQLiteBuilder;

/**
 * Replaces the grammar-based column query with a plain PRAGMA command.
 *
 * Both pragma_table_xinfo() and pragma_table_info() used as table-valued
 * functions in a SELECT … FROM clause require SQLite >= 3.16 / 3.35.
 * The plain PRAGMA form "PRAGMA table_info(tbl)" has been supported since
 * SQLite 2.x and works on every version the server might have.
 */
class SQLiteBuilder extends BaseSQLiteBuilder
{
    public function getColumns($table): array
    {
        $tableWithPrefix = $this->connection->getTablePrefix() . $table;

        $rows = $this->connection->selectFromWriteConnection(
            "PRAGMA table_info({$tableWithPrefix})"
        );

        // First pass: count primary-key columns. Only a SINGLE-column INTEGER PRIMARY KEY
        // is rowid-aliased and auto-increments — composite PKs (pk=1, pk=2, …) must NOT
        // emit "INTEGER primary key autoincrement" on any column or SQLite rejects with
        // "table has more than one primary key" when rebuilding via ALTER TABLE.
        $pkColumnCount = 0;
        foreach ($rows as $row) {
            if ((int) (((array) $row)['pk'] ?? 0) > 0) {
                $pkColumnCount++;
            }
        }

        return array_map(static function ($row) use ($pkColumnCount) {
            $row   = (array) $row;
            $type  = $row['type'] ?? '';

            $typeName = strtolower(trim(preg_replace('/\s*\(.*\)/', '', $type)));

            $isPrimary     = (bool) ($row['pk'] ?? false);
            $autoIncrement = $isPrimary && $typeName === 'integer' && $pkColumnCount === 1;

            return [
                'name'           => $row['name'],
                'type_name'      => $typeName,
                'type'           => $type,
                'nullable'       => !(bool) ($row['notnull'] ?? false),
                'default'        => $row['dflt_value'] ?? null,
                'auto_increment' => $autoIncrement,
                'primary'        => $isPrimary,
                'extra'          => null,
                'collation'      => null,
                'comment'        => null,
                'generation'     => null,
            ];
        }, $rows);
    }

    public function getIndexes($table): array
    {
        $tableWithPrefix = $this->connection->getTablePrefix() . $table;

        // Collect PRIMARY KEY columns from table_info (pk > 0 = part of PK, value = ordinal).
        $pkCols = [];
        foreach ($this->connection->selectFromWriteConnection("PRAGMA table_info({$tableWithPrefix})") as $row) {
            $row = (array) $row;
            if ((int) ($row['pk'] ?? 0) > 0) {
                $pkCols[(int) $row['pk']] = $row['name'];
            }
        }
        ksort($pkCols);
        $pkColValues = array_values($pkCols);

        $indexes = [];
        if (!empty($pkCols)) {
            // BlueprintState::__construct uses match(true) with strict === against booleans,
            // so 1 !== true would leak this synthetic entry into the indexes list and produce
            // `CREATE INDEX "primary"` during ALTER TABLE rebuilds. Match vanilla SQLiteProcessor's bool cast.
            $indexes[] = [
                'name'    => 'primary',
                'columns' => $pkColValues,
                'unique'  => true,
                'primary' => true,
            ];
        }

        foreach ($this->connection->selectFromWriteConnection("PRAGMA index_list({$tableWithPrefix})") as $indexRow) {
            $indexRow = (array) $indexRow;
            $origin  = $indexRow['origin'] ?? '';
            $idxName = $indexRow['name'] ?? '';

            // Collect this index's columns first — needed for the column-set dedup check.
            $cols = [];
            foreach ($this->connection->selectFromWriteConnection("PRAGMA index_info({$idxName})") as $infoRow) {
                $infoRow = (array) $infoRow;
                $cols[(int) $infoRow['seqno']] = $infoRow['name'];
            }
            ksort($cols);
            $colValues = array_values($cols);

            // Skip every representation of the primary key:
            //  • origin='pk'          — newer SQLite standard
            //  • name='primary'       — older SQLite naming convention
            //  • same ordered columns — fallback for old SQLite that uses origin='u'/'c'
            //    with a different name but identical column set
            if ($origin === 'pk' || $idxName === 'primary' || $colValues === $pkColValues) {
                continue;
            }

            $indexes[] = [
                'name'    => $idxName,
                'columns' => $colValues,
                'unique'  => (bool) ($indexRow['unique'] ?? 0),
                'primary' => false,
            ];
        }

        return $indexes;
    }

    public function getForeignKeys($table): array
    {
        $tableWithPrefix = $this->connection->getTablePrefix() . $table;

        $rawRows = $this->connection->selectFromWriteConnection("PRAGMA foreign_key_list({$tableWithPrefix})");

        // Each FK id may span multiple rows (composite FKs). Group by id, preserving seq order.
        $grouped = [];
        foreach ($rawRows as $row) {
            $row = (array) $row;
            $id  = (int) $row['id'];
            if (!isset($grouped[$id])) {
                $grouped[$id] = [
                    'columns'         => [],
                    'foreign_table'   => $row['table'],
                    'foreign_columns' => [],
                    'on_update'       => strtolower($row['on_update'] ?? 'no action'),
                    'on_delete'       => strtolower($row['on_delete'] ?? 'no action'),
                ];
            }
            $seq = (int) $row['seq'];
            $grouped[$id]['columns'][$seq]         = $row['from'];
            $grouped[$id]['foreign_columns'][$seq] = $row['to'];
        }

        return array_values(array_map(static function (array $fk): array {
            ksort($fk['columns']);
            ksort($fk['foreign_columns']);
            return [
                'columns'         => array_values($fk['columns']),
                'foreign_table'   => $fk['foreign_table'],
                'foreign_columns' => array_values($fk['foreign_columns']),
                'on_update'       => $fk['on_update'],
                'on_delete'       => $fk['on_delete'],
            ];
        }, $grouped));
    }

}
