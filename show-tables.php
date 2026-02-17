<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Get all tables
$tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");

echo "\n=== DATABASE TABLES ===\n\n";

foreach ($tables as $table) {
    $tableName = $table->name;
    echo "TABLE: {$tableName}\n";
    echo str_repeat("-", 50) . "\n";
    
    // Get columns for this table
    $columns = DB::select("PRAGMA table_info({$tableName})");
    
    foreach ($columns as $column) {
        $type = $column->type;
        $notnull = $column->notnull ? "NOT NULL" : "NULLABLE";
        $pk = $column->pk ? "PRIMARY KEY" : "";
        echo "  - {$column->name}: {$type} {$notnull} {$pk}\n";
    }
    echo "\n";
}

// Additional info
echo "\n=== SANCTUM TOKENS ===\n";
echo "API tokens are stored in the 'personal_access_tokens' table\n";
echo "Users can create tokens via: \$user->createToken('token-name')->plainTextToken\n";
echo "Usage: Authorization: Bearer {token}\n\n";
