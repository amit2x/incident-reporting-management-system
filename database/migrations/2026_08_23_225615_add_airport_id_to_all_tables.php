<?php

// database/migrations/xxxx_xx_xx_add_airport_id_to_all_tables.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tables that need airport_id
     */
    protected array $tables = [
        'users',
        'incidents',
        'departments',
        'incident_categories',
        'escalation_matrices',
        'kpi_reports',
        'incident_comments',
        'incident_media',
        'escalations',
        'incident_assignments',
        'incident_likes',
        'incident_logs',
        'incident_status_actions',
        'social_posts',
        'user_activity_logs',
    ];

    /**
     * Tables that need airport_id but with special handling
     */
    protected array $specialTables = [
        'users' => [
            'nullable' => true,
            'on_delete' => 'set null',
        ],
        'incidents' => [
            'nullable' => false,
            'on_delete' => 'cascade',
        ],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, ensure airports table exists and has at least one record
        $this->ensureDefaultAirport();

        // Get default airport ID - prefer CCU
        $defaultAirportId = $this->getDefaultAirportId();

        if (!$defaultAirportId) {
            echo "❌ No default airport found! Please seed airports first.\n";
            return;
        }

        echo "ℹ️ Using default airport ID: {$defaultAirportId}\n";

        // First, fix any existing incidents with invalid airport_id
        $this->fixExistingIncidents($defaultAirportId);

        // Process each table
        foreach ($this->tables as $tableName) {
            // Skip if table doesn't exist
            if (!Schema::hasTable($tableName)) {
                echo "⚠️ Table {$tableName} does not exist, skipping...\n";
                continue;
            }

            // Special handling for incidents table
            if ($tableName === 'incidents') {
                $this->processIncidentsTable($defaultAirportId);
                continue;
            }

            // Skip if column already exists
            if (Schema::hasColumn($tableName, 'airport_id')) {
                echo "⏭️ Skipping {$tableName} - airport_id already exists\n";
                continue;
            }

            echo "📝 Processing table: {$tableName}\n";

            // Add airport_id column WITHOUT foreign key constraint first
            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedBigInteger('airport_id')->nullable()->after('id');
                $table->index('airport_id');
            });

            // Set default value for existing records
            DB::table($tableName)->update(['airport_id' => $defaultAirportId]);

            // Make NOT NULL if required
            if (!$this->isNullable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->unsignedBigInteger('airport_id')->nullable(false)->change();
                });
            }

            echo "✅ Added airport_id column to {$tableName}\n";
        }

        // Now add foreign key constraints after all data is set
        echo "\n🔗 Adding foreign key constraints...\n";

        foreach ($this->tables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            if (!Schema::hasColumn($tableName, 'airport_id')) {
                continue;
            }

            try {
                // Check if constraint already exists
                $existingConstraints = DB::select("
                    SELECT CONSTRAINT_NAME
                    FROM information_schema.KEY_COLUMN_USAGE
                    WHERE TABLE_NAME = ?
                    AND COLUMN_NAME = 'airport_id'
                    AND CONSTRAINT_NAME != 'PRIMARY'
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ", [$tableName]);

                if (empty($existingConstraints)) {
                    Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                        $onDelete = $this->getOnDeleteAction($tableName);
                        $table->foreign('airport_id')
                            ->references('id')
                            ->on('airports')
                            ->onDelete($onDelete);
                    });
                    echo "✅ Added foreign key constraint to {$tableName}\n";
                } else {
                    echo "⏭️ Foreign key constraint already exists on {$tableName}\n";
                }
            } catch (\Exception $e) {
                echo "⚠️ Could not add foreign key to {$tableName}: " . $e->getMessage() . "\n";
            }
        }

        // Create composite indexes for better performance
        $this->createCompositeIndexes();

        echo "\n🎉 All airport_id columns added successfully!\n";
    }

    /**
     * Fix existing incidents with invalid airport_id
     */
    protected function fixExistingIncidents($defaultAirportId): void
    {
        if (!Schema::hasTable('incidents')) {
            return;
        }

        if (!Schema::hasColumn('incidents', 'airport_id')) {
            return;
        }

        // Check if there are incidents with invalid airport_id (0 or null)
        $invalidIncidents = DB::table('incidents')
            ->where(function ($query) {
                $query->where('airport_id', 0)
                    ->orWhereNull('airport_id');
            })
            ->count();

        if ($invalidIncidents > 0) {
            echo "🔧 Fixing {$invalidIncidents} incidents with invalid airport_id...\n";

            // Update invalid incidents with default airport_id
            DB::table('incidents')
                ->where(function ($query) {
                    $query->where('airport_id', 0)
                        ->orWhereNull('airport_id');
                })
                ->update(['airport_id' => $defaultAirportId]);

            echo "✅ Fixed incidents with invalid airport_id\n";
        }
    }

    /**
     * Process incidents table specifically
     */
    protected function processIncidentsTable($defaultAirportId): void
    {
        echo "📝 Processing table: incidents\n";

        // Check if column already exists
        if (Schema::hasColumn('incidents', 'airport_id')) {
            // Fix any invalid values
            $this->fixExistingIncidents($defaultAirportId);

            // Check if column is after id
            try {
                $columns = DB::select("
                    SELECT COLUMN_NAME, ORDINAL_POSITION
                    FROM information_schema.COLUMNS
                    WHERE TABLE_NAME = 'incidents'
                    AND TABLE_SCHEMA = DATABASE()
                    ORDER BY ORDINAL_POSITION
                ");

                $idPosition = null;
                $airportPosition = null;

                foreach ($columns as $index => $column) {
                    if ($column->COLUMN_NAME === 'id') {
                        $idPosition = $index + 1;
                    }
                    if ($column->COLUMN_NAME === 'airport_id') {
                        $airportPosition = $index + 1;
                    }
                }

                if ($airportPosition && $idPosition && $airportPosition !== $idPosition + 1) {
                    echo "⚠️ airport_id is not immediately after id. Recreating column...\n";

                    // Drop and recreate the column
                    Schema::table('incidents', function (Blueprint $table) {
                        $table->dropForeign(['airport_id']);
                        $table->dropIndex(['airport_id']);
                        $table->dropColumn('airport_id');
                    });

                    // Re-add the column after id
                    Schema::table('incidents', function (Blueprint $table) {
                        $table->unsignedBigInteger('airport_id')->nullable(false)->after('id');
                        $table->index('airport_id');
                    });

                    // Update with default value
                    DB::table('incidents')->update(['airport_id' => $defaultAirportId]);

                    echo "✅ Recreated airport_id after id in incidents table\n";
                } else {
                    echo "⏭️ airport_id is already in correct position in incidents table\n";
                }
            } catch (\Exception $e) {
                echo "⚠️ Could not verify column position: " . $e->getMessage() . "\n";
            }

            echo "✅ airport_id already exists in incidents table\n";
            return;
        }

        // Add airport_id column WITHOUT foreign key constraint first
        Schema::table('incidents', function (Blueprint $table) {
            $table->unsignedBigInteger('airport_id')->nullable()->after('id');
            $table->index('airport_id');
        });

        // Set default value for existing records
        DB::table('incidents')->update(['airport_id' => $defaultAirportId]);

        // Make NOT NULL if required
        if (!$this->isNullable('incidents')) {
            Schema::table('incidents', function (Blueprint $table) {
                $table->unsignedBigInteger('airport_id')->nullable(false)->change();
            });
        }

        echo "✅ Added airport_id column to incidents\n";
    }

    /**
     * Get default airport ID (prefer CCU)
     */
    protected function getDefaultAirportId(): ?int
    {
        // First, try to find CCU
        $ccuAirport = DB::table('airports')->where('code', 'CCU')->first();

        if ($ccuAirport) {
            return $ccuAirport->id;
        }

        // If CCU not found, get any active airport
        $anyAirport = DB::table('airports')->where('is_active', true)->first();

        if ($anyAirport) {
            return $anyAirport->id;
        }

        // If no active airports, get any airport
        $anyAirport = DB::table('airports')->first();

        if ($anyAirport) {
            return $anyAirport->id;
        }

        return null;
    }

    /**
     * Determine if airport_id should be nullable for this table
     */
    protected function isNullable(string $table): bool
    {
        return $this->specialTables[$table]['nullable'] ?? false;
    }

    /**
     * Get ON DELETE action for this table
     */
    protected function getOnDeleteAction(string $table): string
    {
        return $this->specialTables[$table]['on_delete'] ?? 'cascade';
    }

    /**
     * Ensure default airport exists
     */
    protected function ensureDefaultAirport(): void
    {
        if (!Schema::hasTable('airports')) {
            echo "❌ Airports table does not exist! Creating airports table first...\n";

            Schema::create('airports', function (Blueprint $table) {
                $table->id();
                $table->string('code', 10)->unique();
                $table->string('icao_code', 4)->unique()->nullable();
                $table->string('iata_code', 3)->unique()->nullable();
                $table->string('name', 191);
                $table->text('description')->nullable();
                $table->string('address')->nullable();
                $table->string('city', 100);
                $table->string('state', 100)->nullable();
                $table->string('country', 100)->default('India');
                $table->string('timezone', 50)->default('Asia/Kolkata');
                $table->decimal('latitude', 10, 8)->nullable();
                $table->decimal('longitude', 11, 8)->nullable();
                $table->string('contact_email', 100)->nullable();
                $table->string('contact_phone', 20)->nullable();
                $table->string('website', 255)->nullable();
                $table->string('logo')->nullable();
                $table->string('primary_color', 7)->default('#3B82F6');
                $table->json('settings')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
                $table->softDeletes();

                $table->index('code');
                $table->index('city');
                $table->index('country');
                $table->index('is_active');
            });

            echo "✅ Airports table created.\n";
        }

        // Check if there's at least one airport
        $airportCount = DB::table('airports')->count();

        if ($airportCount === 0) {
            echo "ℹ️ No airports found. Creating default airport (CCU)...\n";

            DB::table('airports')->insert([
                'code' => 'CCU',
                'icao_code' => 'VECC',
                'iata_code' => 'CCU',
                'name' => 'NSCBI Airport',
                'description' => 'Netaji Subhash Chandra Bose International Airport, Kolkata',
                'address' => 'Jessore Road, Dum Dum',
                'city' => 'Kolkata',
                'state' => 'West Bengal',
                'country' => 'India',
                'timezone' => 'Asia/Kolkata',
                'latitude' => 22.6547,
                'longitude' => 88.4467,
                'contact_email' => 'info@nscbiairport.com',
                'contact_phone' => '+91-33-12345678',
                'is_active' => true,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $airportId = DB::table('airports')->value('id');
            echo "✅ Default airport created with ID: {$airportId}\n";
        }
    }

    /**
     * Create composite indexes for better performance
     */
    protected function createCompositeIndexes(): void
    {
        $indexes = [
            'incidents' => ['airport_id', 'status', 'created_at'],
            'incidents' => ['airport_id', 'severity', 'priority'],
            'users' => ['airport_id', 'department_id'],
            'departments' => ['airport_id', 'is_active'],
            'incident_categories' => ['airport_id', 'is_active'],
        ];

        foreach ($indexes as $table => $columns) {
            if (Schema::hasTable($table)) {
                $indexName = $table . '_' . implode('_', $columns) . '_index';

                try {
                    // Check if index exists
                    $existingIndexes = DB::select("
                        SHOW INDEX FROM {$table} WHERE Key_name = ?
                    ", [$indexName]);

                    if (empty($existingIndexes)) {
                        Schema::table($table, function (Blueprint $table) use ($columns, $indexName) {
                            $table->index($columns, $indexName);
                        });
                        echo "✅ Created composite index on {$table}: " . implode(', ', $columns) . "\n";
                    } else {
                        echo "⏭️ Composite index already exists on {$table}\n";
                    }
                } catch (\Exception $e) {
                    echo "⚠️ Could not create composite index on {$table}: " . $e->getMessage() . "\n";
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'airport_id')) {
                try {
                    // Drop foreign key first
                    Schema::table($tableName, function (Blueprint $table) {
                        $table->dropForeign(['airport_id']);
                    });

                    // Drop column
                    Schema::table($tableName, function (Blueprint $table) {
                        $table->dropColumn('airport_id');
                    });

                    echo "✅ Removed airport_id from {$tableName}\n";
                } catch (\Exception $e) {
                    echo "⚠️ Could not remove airport_id from {$tableName}: " . $e->getMessage() . "\n";
                }
            }
        }
    }
};
