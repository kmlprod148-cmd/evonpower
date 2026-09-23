<?php

namespace App\Database\Grammars;

use Illuminate\Database\Schema\Grammars\SQLiteGrammar as BaseSQLiteGrammar;

/**
 * Compatibility grammar for SQLite < 3.35.
 *
 * Laravel 11 uses pragma_table_xinfo() as a table-valued function which was
 * added in SQLite 3.35.0. This override uses pragma_table_info() instead,
 * which has been available as a table-valued function since SQLite 3.16.0.
 * The only difference is the absence of the `hidden` column; we return 0
 * for `extra` which is sufficient for column-existence checks.
 */
class SQLiteGrammar extends BaseSQLiteGrammar
{
    public function compileColumns($table): string
    {
        return sprintf(
            'select name, type, not "notnull" as "nullable", dflt_value as "default", '
            .'pk as "primary", 0 as "extra" '
            .'from pragma_table_info(%s) order by cid asc',
            $this->quoteString(str_replace('.', '__', $table))
        );
    }
}
