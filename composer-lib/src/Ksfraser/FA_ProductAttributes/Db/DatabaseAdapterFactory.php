<?php

namespace Ksfraser\FA_ProductAttributes\Db;

/**
 * Factory for creating database adapters based on driver type
 */
class DatabaseAdapterFactory
{
    /**
     * Create a database adapter for the specified driver
     *
     * @param string $driver The driver type ('fa', 'pdo', 'mysql')
     * @param string|null $prefix Custom table prefix (for FA driver)
     * @return DbAdapterInterface
     */
    public static function create(string $driver = 'fa', ?string $prefix = null): DbAdapterInterface
    {
        switch ($driver) {
            case 'fa':
                return new FrontAccountingDbAdapter($prefix);
            case 'pdo':
                return new PdoDbAdapter();
            case 'mysql':
                return new MysqlDbAdapter();
            default:
                throw new \InvalidArgumentException("Unknown database driver: $driver");
        }
    }
}