<?php

namespace Ibrows\DoctrineDblibSqlDriver\Platform;

use Doctrine\DBAL\Platforms\SQLServerPlatform;
use Ibrows\DoctrineDblibSqlDriver\Driver\PDODblibDriver;

class SQLServer2012Platform extends SQLServerPlatform
{
    /**
     * @var PDODblibDriver
     */
    protected $driver;

    /**
     * {@inheritDoc}
     */
    protected function initializeDoctrineTypeMappings()
    {
        parent::initializeDoctrineTypeMappings();
        $this->doctrineTypeMapping += array(
            'date' => 'date',
            'time' => 'time'
        );
    }

    /**
     * @return string
     */
    public function getDateTimeFormatString()
    {
        return 'M d Y H:i:s:000A';
    }

    /**
     * @return string
     */
    public function getDateFormatString()
    {
        return 'Y-m-d';
    }

    /**
     * @return string
     */
    public function getTimeFormatString()
    {
        return 'H:i:s:000A';
    }

    /**
     * {@inheritDoc}
     */
    public function getDateTypeDeclarationSQL(array $fieldDeclaration)
    {
        return 'DATE';
    }

    /**
     * {@inheritDoc}
     */
    public function getTimeTypeDeclarationSQL(array $fieldDeclaration)
    {
        return 'TIME';
    }

    /**
     * @return bool
     */
    public function supportsLimitOffset()
    {
        return true;
    }

    /**
     * @param PDODblibDriver $driver
     */
    public function setDriver(PDODblibDriver $driver)
    {
        $this->driver = $driver;
    }

    /**
     * @param string $prefix
     * @return array
     */
    protected function getDriverExtraOptions($prefix = 'CREATE_')
    {
        if(!$this->driver){
            return array();
        }
        return $this->driver->getExtraOptions($prefix);
    }
    /**
     * {@inheritdoc}
     *
     * Modifies column declaration order as it differs in Microsoft SQL Server.
     */
    public function getColumnDeclarationSQL($name, array $field)
    {
        if (isset($field['columnDefinition'])) {
            $columnDef = $this->getCustomTypeDeclarationSQL($field);
        } else {
            $default = $this->getDefaultValueDeclarationSQL($field);

            $collation = (isset($field['collate']) && $field['collate']) ?
                ' ' . $this->getColumnCollationDeclarationSQL($field['collate']) : '';

            $notnull = (isset($field['notnull']) && $field['notnull']) ? ' NOT NULL' : '';

            $unique = (isset($field['unique']) && $field['unique']) ?
                ' ' . $this->getUniqueFieldDeclarationSQL() : '';

            $check = (isset($field['check']) && $field['check']) ?
                ' ' . $field['check'] : '';

            $typeDecl = $field['type']->getSqlDeclaration($field, $this);
            $columnDef = $typeDecl . $collation . $notnull . $default . $unique . $check;
        }

        return $name . ' ' . $columnDef;
    }
    /**
     * @param string $tableName
     * @param array $columns
     * @param array $options
     * @return array
     */
    protected function _getCreateTableSQL($tableName, array $columns, array $options = array())
    {
        $sqls = parent::_getCreateTableSQL($tableName, $columns, $options);
        foreach ($this->getDriverExtraOptions() as $optionName => $value) {
            $sql = "SET " . strtoupper($optionName);
            if ($value) {
                $sql .= " ON";
            } else {
                $sql .= " OFF";
            }
            array_unshift($sqls, $sql);
        }
        return $sqls;
    }

    /**
     * {@inheritDoc}
     */
    public function getClobTypeDeclarationSQL(array $field)
    {
        return 'VARCHAR (max)';
    }
}
