<?php

namespace App\Database;

use Illuminate\Database\SQLiteConnection as BaseSQLiteConnection;

/**
 * Custom SQLite connection that injects our compatibility schema builder.
 * The builder uses plain "PRAGMA table_info(tbl)" instead of the
 * table-valued-function form which requires SQLite >= 3.16 / 3.35.
 */
class SQLiteConnection extends BaseSQLiteConnection
{
    public function getSchemaBuilder(): SQLiteBuilder
    {
        if (is_null($this->schemaGrammar)) {
            $this->useDefaultSchemaGrammar();
        }

        return new SQLiteBuilder($this);
    }
}
