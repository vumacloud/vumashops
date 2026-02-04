<?php

declare(strict_types=1);

use Stancl\Tenancy\Database\Models\Domain;
use App\Models\Tenant;

return [
    /**
     * Use our custom Tenant model
     */
    'tenant_model' => Tenant::class,

    /**
     * Use UUID for tenant IDs
     */
    'id_generator' => Stancl\Tenancy\UUIDGenerator::class,

    'domain_model' => Domain::class,

    /**
     * Central domains - these are NOT tenant domains
     * shops.vumacloud.com is the platform admin
     */
    'central_domains' => [
        'shops.vumacloud.com',
        'localhost',
        '127.0.0.1',
    ],

    /**
     * Tenancy bootstrappers are executed when tenancy is initialized.
     */
    'bootstrappers' => [
        Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\CacheTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\RedisTenancyBootstrapper::class,
    ],

    /**
     * Database tenancy config.
     */
    'database' => [
        'central_connection' => env('DB_CONNECTION', 'central'),

        /**
         * Connection used as a "template" for tenant database connection.
         */
        'template_tenant_connection' => null,

        /**
         * Tenant database names: vumashops_tenant_{uuid}
         */
        'prefix' => 'vumashops_tenant_',
        'suffix' => '',

        /**
         * Database managers
         */
        'managers' => [
            'sqlite' => Stancl\Tenancy\TenantDatabaseManagers\SQLiteDatabaseManager::class,
            'mysql' => Stancl\Tenancy\TenantDatabaseManagers\MySQLDatabaseManager::class,
            'pgsql' => Stancl\Tenancy\TenantDatabaseManagers\PostgreSQLDatabaseManager::class,
        ],
    ],

    /**
     * Cache tenancy config.
     * Tags cache with tenant ID for isolation.
     */
    'cache' => [
        'tag_base' => 'tenant',
    ],

    /**
     * Filesystem tenancy config.
     * We use DO Spaces (S3) so we suffix the path with tenant ID.
     */
    'filesystem' => [
        'suffix_base' => 'tenant',
        'disks' => [
            'local',
            'public',
            's3', // DO Spaces
        ],

        'root_override' => [
            'local' => '%storage_path%/app/',
            'public' => '%storage_path%/app/public/',
        ],

        'suffix_storage_path' => true,
        'asset_helper_tenancy' => true,
    ],

    /**
     * Redis tenancy config.
     * All Redis keys are prefixed with tenant:{id}: for isolation.
     */
    'redis' => [
        'prefix_base' => 'tenant',
        'prefixed_connections' => [
            'default',
            'cache',
            'session',
            'queue',
        ],
    ],

    /**
     * Features
     */
    'features' => [
        Stancl\Tenancy\Features\ViteBundler::class,
    ],

    /**
     * Enable tenancy routes
     */
    'routes' => true,

    /**
     * Migration parameters for tenant databases
     */
    'migration_parameters' => [
        '--force' => true,
        '--path' => [database_path('migrations/tenant')],
        '--realpath' => true,
    ],

    /**
     * Seeder parameters
     */
    'seeder_parameters' => [
        '--class' => 'Database\\Seeders\\TenantDatabaseSeeder',
        '--force' => true,
    ],
];
