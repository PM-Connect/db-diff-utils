<?php

namespace PMConnect\DBDiff\Utils;

use PMConnect\DBDiff\Utils\Contracts\Diff as DiffContract;
use PMConnect\DBDiff\Utils\Contracts\Output as OutputContract;
use Doctrine\DBAL\Schema\Table;
use Illuminate\Database\Connection;

class TableStructure implements DiffContract
{
    /**
     * @var OutputContract
     */
    protected $output;

    /**
     * @var Connection
     */
    protected $db1;

    /**
     * @var Connection
     */
    protected $db2;

    public function __construct(OutputContract $output, Connection $connection1, Connection $connection2)
    {
        $this->output = $output;
        $this->db1 = $connection1;
        $this->db2 = $connection2;
    }

    /**
     * Run the diff.
     */
    public function diff()
    {
        $tables = $this->tablesInDb($this->db1);
        $db2Tables = $this->tablesInDb($this->db2);

        foreach ($tables as $table) {
            if (in_array($table, $db2Tables)) {
                $this->diffTableSchema($table);
            }
        }
    }

    /**
     * Get all tables in  a given database connection.
     *
     * @param Connection $db
     * @return array
     */
    protected function tablesInDb(Connection $db) : array
    {
        return $db->getDoctrineSchemaManager()->listTableNames();
    }

    /**
     * Run the diff on the database schema.
     *
     * @param string $table
     */
    protected function diffTableSchema(string $table)
    {
        $db1Schema = $this->db1->getDoctrineSchemaManager();
        $db2Schema = $this->db2->getDoctrineSchemaManager();

        $db1Table = $db1Schema->listTableDetails($table);
        $db2Table = $db2Schema->listTableDetails($table);

        $this->diffColumns($db1Table, $db2Table);
    }

    /**
     * Calculate the difference in table columns.
     *
     * @param Table $table1
     * @param Table $table2
     */
    protected function diffColumns(Table $table1, Table $table2)
    {
        $db1Columns = $table1->getColumns();
        $db2Columns = $table2->getColumns();

        $this->diffColumnNames($table1->getName(), $db1Columns, $db2Columns);
        $this->diffColumnStructure($table1->getName(), $db1Columns, $db2Columns);
        $this->diffTableIndexes($table1->getName(), $table1, $table1);
        $this->diffTableForeignKeys($table1->getName(), $table1, $table2);
    }

    /**
     * Calculate the difference in table column names.
     *
     * @param string $table
     * @param \Doctrine\DBAL\Schema\Column[] $db1Columns
     * @param \Doctrine\DBAL\Schema\Column[] $db2Columns
     */
    protected function diffColumnNames(string $table, array $db1Columns, array $db2Columns)
    {
        $db1Name = $this->db1->getDatabaseName();
        $db2Name = $this->db2->getDatabaseName();

        $context = [
            'table' => $table,
            'database' => $db1Name
        ];

        foreach (array_keys($db1Columns) as $name) {
            if (!in_array($name, array_keys($db2Columns))) {
                $this->output->write(
                    $db1Name . ':' . $table . ':' . $name . ' was not found in database "' . $db2Name . '".',
                    array_merge($context, [
                        'column' => $name,
                        'type' => 'column_exists_in_comparison',
                        'result' => false
                    ])
                );
            } else {
                $this->output->write(null, array_merge($context, [
                    'column' => $name,
                    'type' => 'column_exists_in_comparison',
                    'result' => true
                ]));
            }
        }

        foreach (array_keys($db2Columns) as $name) {
            if (!in_array($name, array_keys($db1Columns))) {
                $this->output->write(
                    $db2Name . ':' . $table . ':' . $name . ' was not found in database "' . $db1Name . '".',
                    array_merge($context, [
                        'column' => $name,
                        'type' => 'column_exists_in_primary',
                        'result' => false
                    ])
                );
            } else {
                $this->output->write(null, array_merge($context, [
                    'column' => $name,
                    'type' => 'column_exists_in_primary',
                    'result' => true
                ]));
            }
        }
    }

    /**
     * Calculate the difference in database column structures.
     *
     * @param string $table
     * @param \Doctrine\DBAL\Schema\Column[] $db1Columns
     * @param \Doctrine\DBAL\Schema\Column[] $db2Columns
     */
    protected function diffColumnStructure(string $table, array $db1Columns, array $db2Columns)
    {
        $db1Name = $this->db1->getDatabaseName();
        $db2Name = $this->db2->getDatabaseName();

        $context = [
            'table' => $table,
            'database' => $this->db1->getDatabaseName(),
            'type' => 'column_structure'
        ];

        foreach ($db1Columns as $name => $column1) {
            if (!in_array($name, array_keys($db2Columns))) {
                continue;
            }

            $column2 = $db2Columns[$name];

            $column1Type = $column1->getType();
            $column2Type = $column2->getType();

            if ($column1Type->getName() != $column2Type->getName()) {
                $this->output->write(null, array_merge($context, [
                    'column' => $name,
                    'field' => 'type',
                    'result' => false
                ]));
            } else {
                $this->output->write(null, array_merge($context, [
                    'column' => $name,
                    'field' => 'type',
                    'result' => true
                ]));
            }

            $column1Length = $column1->getLength() ?? $column1Type->getDefaultLength($this->db1->getDoctrineConnection()->getDatabasePlatform());
            $column2Length = $column2->getLength() ?? $column1Type->getDefaultLength($this->db2->getDoctrineConnection()->getDatabasePlatform());

            if ($column1Length != $column2Length) {
                $message = $db1Name . ':' . $table . ':' . $name . ':' . $column1Length
                    . ' vs '
                    . $db2Name . ':' . $table . ':' . $name . ':' . $column2Length;

                $this->output->write($message, array_merge($context, [
                    'column' => $name,
                    'field' => 'length',
                    'result' => false
                ]));
            } else {
                $this->output->write(null, array_merge($context, [
                    'column' => $name,
                    'field' => 'length',
                    'result' => true
                ]));
            }

            if ($column1->getNotnull() != $column2->getNotnull()) {
                if ($column2->getNotnull()) {
                    $message = $db2Name . ':' . $table . ':' . $name . ' is NOT nullable.';
                } else {
                    $message = $db2Name . ':' . $table . ':' . $name . ' is nullable.';
                }

                $this->output->write($message, array_merge($context, [
                    'column' => $name,
                    'field' => 'nullable',
                    'result' => false
                ]));
            } else {
                $this->output->write(null, array_merge($context, [
                    'column' => $name,
                    'field' => 'nullable',
                    'result' => true
                ]));
            }

            if ($column1->getDefault() != $column2->getDefault()) {
                $message = $db1Name . ':' . $table . ':' . $name . ':' . $column1->getDefault()
                    . ' vs '
                    . $db2Name . ':' . $table . ':' . $name . ':' . $column2->getDefault();

                $this->output->write($message, array_merge($context, [
                    'column' => $name,
                    'field' => 'default',
                    'result' => false
                ]));
            } else {
                $this->output->write(null, array_merge($context, [
                    'column' => $name,
                    'field' => 'default',
                    'result' => true
                ]));
            }

            if ($column1->getUnsigned() != $column2->getUnsigned()) {
                if ($column2->getUnsigned()) {
                    $message = $db2Name . ':' . $table . ':' . $name . ' is unsigned.';
                } else {
                    $message = $db2Name . ':' . $table . ':' . $name . ' is NOT unsigned.';
                }

                $this->output->write($message, array_merge($context, [
                    'column' => $name,
                    'field' => 'unsigned',
                    'result' => false
                ]));
            } else {
                $this->output->write(null, array_merge($context, [
                    'column' => $name,
                    'field' => 'unsigned',
                    'result' => true
                ]));
            }

            if ($column1->getAutoincrement() != $column2->getAutoincrement()) {
                if ($column2->getAutoincrement()) {
                    $message = $db2Name . ':' . $table . ':' . $name . ' is auto_incrementable.';
                } else {
                    $message = $db2Name . ':' . $table . ':' . $name . ' is NOT auto_incrementable.';
                }

                $this->output->write($message, array_merge($context, [
                    'column' => $name,
                    'field' => 'auto_increment',
                    'result' => false
                ]));
            } else {
                $this->output->write(null, array_merge($context, [
                    'column' => $name,
                    'field' => 'auto_increment',
                    'result' => true
                ]));
            }
        }
    }

    /**
     * Calculate the difference in database table indexes.
     *
     * @param string $table
     * @param Table $table1
     * @param Table $table2
     * @internal param Table $db1Columns
     * @internal param Table $db2Columns
     */
    protected function diffTableIndexes(string $table, Table $table1, Table $table2)
    {
        $db1Name = $this->db1->getDatabaseName();
        $db2Name = $this->db2->getDatabaseName();

        $context = [
            'table' => $table,
            'database' => $db1Name,
            'type' => 'table_index'
        ];

        $table1Indexes = $table1->getIndexes();
        $table2Indexes = $table2->getIndexes();

        foreach ($table1Indexes as $name => $index1) {
            if (!array_key_exists($name, $table2Indexes)) {
                $this->output->write(
                    'Index "' . $name . '" does not exist in "' . $db2Name . '".',
                    array_merge($context, [
                        'column' => $name,
                        'type' => 'table_index_exists',
                        'result' => false
                    ])
                );

                continue;
            }

            $index2 = $table2Indexes[$name];

            $this->output->write(null, array_merge($context, [
                'column' => $name,
                'type' => 'table_index_exists',
                'result' => true
            ]));

            if ($index1->isPrimary() != $index2->isPrimary()) {
                if ($index2->isPrimary()) {
                    $message = $db2Name . ':' . $table . ':' . $name . ' is primary index.';
                } else {
                    $message = $db2Name . ':' . $table . ':' . $name . ' is NOT primary index.';
                }

                $this->output->write($message, array_merge($context, [
                    'column' => $name,
                    'field' => 'primary_index',
                    'result' => false
                ]));
            } else {
                $this->output->write(null, array_merge($context, [
                    'column' => $name,
                    'field' => 'primary_index',
                    'result' => true
                ]));
            }

            if ($index1->isUnique() != $index2->isUnique()) {
                if ($index2->isUnique()) {
                    $message = $db2Name . ':' . $table . ':' . $name . ' is unique index.';
                } else {
                    $message = $db2Name . ':' . $table . ':' . $name . ' is NOT unique index.';
                }

                $this->output->write($message, array_merge($context, [
                    'column' => $name,
                    'field' => 'unique_index',
                    'result' => false
                ]));
            } else {
                $this->output->write(null, array_merge($context, [
                    'column' => $name,
                    'field' => 'unique_index',
                    'result' => true
                ]));
            }

            if ($index1->getColumns() != $index2->getColumns()) {
                $message = $db1Name . ':' . $table . ':' . $name . ' columns ' . '(' . implode(', ', $index1->getColumns()) . ')'
                    . ' vs '
                    . $db2Name . ':' . $table . ':' . $name . ' columns ' . '(' . implode(', ', $index2->getColumns()) . ')';

                $this->output->write($message, array_merge($context, [
                    'column' => $name,
                    'field' => 'index_columns',
                    'result' => false
                ]));
            } else {
                $this->output->write(null, array_merge($context, [
                    'column' => $name,
                    'field' => 'index_columns',
                    'result' => true
                ]));
            }

            if ($index1->getFlags() != $index2->getFlags()) {
                $message = $db1Name . ':' . $table . ':' . $name . ' flags ' . '(' . implode(', ', $index1->getFlags()) . ')'
                    . ' vs '
                    . $db2Name . ':' . $table . ':' . $name . ' flags ' . '(' . implode(', ', $index2->getFlags()) . ')';

                $this->output->write($message, array_merge($context, [
                    'column' => $name,
                    'field' => 'index_flags',
                    'result' => false
                ]));
            } else {
                $this->output->write(null, array_merge($context, [
                    'column' => $name,
                    'field' => 'index_flags',
                    'result' => true
                ]));
            }

            if ($index1->getOptions() != $index2->getOptions()) {
                $message = $db1Name . ':' . $table . ':' . $name . ' options ' . '(' . implode(', ', $index1->getOptions()) . ')'
                    . ' vs '
                    . $db2Name . ':' . $table . ':' . $name . ' options ' . '(' . implode(', ', $index2->getOptions()) . ')';

                $this->output->write($message, array_merge($context, [
                    'column' => $name,
                    'field' => 'index_options',
                    'result' => false
                ]));
            } else {
                $this->output->write(null, array_merge($context, [
                    'column' => $name,
                    'field' => 'index_options',
                    'result' => true
                ]));
            }
        }
    }

    /**
     * Calculate the difference in database table foreign keys.
     *
     * @param string $table
     * @param Table $table1
     * @param Table $table2
     * @internal param Table $db1Columns
     * @internal param Table $db2Columns
     */
    protected function diffTableForeignKeys(string $table, Table $table1, Table $table2)
    {
        $db1Name = $this->db1->getDatabaseName();
        $db2Name = $this->db2->getDatabaseName();

        $context = [
            'table' => $table,
            'database' => $db1Name,
            'type' => 'table_foreign_key'
        ];

        $table1Keys = $table1->getForeignKeys();
        $table2Keys = $table2->getForeignKeys();

        foreach ($table1Keys as $name => $key1) {
            if (!array_key_exists($name, $table2Keys)) {
                $this->output->write(
                    'Foreign Key "' . $name . '" does not exist in "' . $db2Name . '".',
                    array_merge($context, [
                        'column' => $name,
                        'type' => 'table_foreign_key_exists',
                        'result' => false
                    ])
                );

                continue;
            }

            $key2 = $table2Keys[$name];

            $this->output->write(null, array_merge($context, [
                'column' => $name,
                'type' => 'table_foreign_key_exists',
                'result' => true
            ]));

            if ($key1->getLocalTableName() != $key2->getLocalTableName()) {
                $message = $db1Name . ':' . $table . ':' . $name . ' local table ' . $key1->getLocalTableName()
                    . ' vs '
                    . $db2Name . ':' . $table . ':' . $name . ' local table ' . $key2->getLocalTableName();

                $this->output->write($message, array_merge($context, [
                    'column' => $name,
                    'field' => 'foreign_key_name',
                    'result' => false
                ]));
            } else {
                $this->output->write(null, array_merge($context, [
                    'column' => $name,
                    'field' => 'foreign_key_name',
                    'result' => true
                ]));
            }

            if ($key1->getForeignTableName() != $key2->getForeignTableName()) {
                $message = $db1Name . ':' . $table . ':' . $name . ' local table ' . $key1->getForeignTableName()
                    . ' vs '
                    . $db2Name . ':' . $table . ':' . $name . ' local table ' . $key2->getForeignTableName();

                $this->output->write($message, array_merge($context, [
                    'column' => $name,
                    'field' => 'foreign_table_name',
                    'result' => false
                ]));
            } else {
                $this->output->write(null, array_merge($context, [
                    'column' => $name,
                    'field' => 'foreign_table_name',
                    'result' => true
                ]));
            }

            if ($key1->getLocalColumns() != $key2->getLocalColumns()) {
                $message = $db1Name . ':' . $table . ':' . $name . ' local columns ' . '(' . implode(', ', $key1->getLocalColumns()) . ')'
                    . ' vs '
                    . $db2Name . ':' . $table . ':' . $name . ' local columns ' . '(' . implode(', ', $key2->getLocalColumns()) . ')';

                $this->output->write($message, array_merge($context, [
                    'column' => $name,
                    'field' => 'foreign_key_local_columns',
                    'result' => false
                ]));
            } else {
                $this->output->write(null, array_merge($context, [
                    'column' => $name,
                    'field' => 'foreign_key_local_columns',
                    'result' => true
                ]));
            }

            if ($key1->getForeignColumns() != $key2->getForeignColumns()) {
                $message = $db1Name . ':' . $table . ':' . $name . ' foreign columns ' . '(' . implode(', ', $key1->getForeignColumns()) . ')'
                    . ' vs '
                    . $db2Name . ':' . $table . ':' . $name . ' foreign columns ' . '(' . implode(', ', $key2->getForeignColumns()) . ')';

                $this->output->write($message, array_merge($context, [
                    'column' => $name,
                    'field' => 'foreign_key_foreign_columns',
                    'result' => false
                ]));
            } else {
                $this->output->write(null, array_merge($context, [
                    'column' => $name,
                    'field' => 'foreign_key_foreign_columns',
                    'result' => true
                ]));
            }

            if ($key1->onUpdate() != $key2->onUpdate()) {
                $message = $db2Name . ':' . $table . ':' . $name . ' on update "'.$key1->onUpdate().'".';

                $this->output->write($message, array_merge($context, [
                    'column' => $name,
                    'field' => 'foreign_key_on_update',
                    'result' => false
                ]));
            } else {
                $this->output->write(null, array_merge($context, [
                    'column' => $name,
                    'field' => 'foreign_key_on_update',
                    'result' => true
                ]));
            }

            if ($key1->onDelete() != $key2->onDelete()) {
                $message = $db2Name . ':' . $table . ':' . $name . ' on delete "'.$key1->onDelete().'".';

                $this->output->write($message, array_merge($context, [
                    'column' => $name,
                    'field' => 'foreign_key_on_delete',
                    'result' => false
                ]));
            } else {
                $this->output->write(null, array_merge($context, [
                    'column' => $name,
                    'field' => 'foreign_key_on_delete',
                    'result' => true
                ]));
            }
        }
    }
}
