<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class DiagnoseBusinessProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (DB::getDriverName() === 'mysql') {
            // Get the table columns for MySQL
            $columns = DB::select('SHOW COLUMNS FROM business_profiles');
            
            // Output the columns to the log
            foreach ($columns as $column) {
                // Specifically check for 'NO' in the Null field which indicates it's required
                $isRequired = $column->Null === 'NO' && $column->Default === null;
                
                // Log the column information with a clear format
                \Log::info("Column: {$column->Field}, Type: {$column->Type}, Required: " . 
                           ($isRequired ? 'YES' : 'NO'));
            }
        } else {
            // Pour SQLite, utiliser PRAGMA
            $columns = DB::select('PRAGMA table_info(business_profiles)');
            
            // Output the columns to the log
            foreach ($columns as $column) {
                $isRequired = $column->notnull === 1 && $column->dflt_value === null;
                
                // Log the column information with a clear format
                \Log::info("Column: {$column->name}, Type: {$column->type}, Required: " . 
                           ($isRequired ? 'YES' : 'NO'));
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Nothing to reverse
    }
}
