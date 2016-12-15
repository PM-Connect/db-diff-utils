<?php

namespace PMConnect\DBDiff\Utils;

use PMConnect\DBDiff\Utils\Contracts\Diff as DiffContract;
use PMConnect\DBDiff\Utils\Contracts\Output as OutputContract;
use Illuminate\Database\Connection;

class TableNames implements DiffContract
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
        $db1Tables = $this->tablesInDb($this->db1);
        $db2Tables = $this->tablesInDb($this->db2);

        $db1Name = $this->db1->getDatabaseName();
        $db2Name = $this->db2->getDatabaseName();

        foreach ($db1Tables as $table) {
            $this->checkDifference($db1Name, $table, $db2Tables, 'comparison', $db2Name);
        }

        foreach ($db2Tables as $table) {
            $this->checkDifference($db2Name, $table, $db1Tables, 'primary', $db1Name);
        }
    }

    /**
     * Fetch all tables in a given database connection.
     *
     * @param Connection $db
     * @return array
     */
    protected function tablesInDb(Connection $db) : array
    {
        return $db->getDoctrineSchemaManager()->listTableNames();
    }

    /**
     * Check the given table is in the other tables array.
     *
     * @param string $databaseName
     * @param string $table
     * @param array $otherTables
     * @param string $type
     * @param $secondaryDatabaseName
     */
    protected function checkDifference(string $databaseName, string $table, array $otherTables, $type, $secondaryDatabaseName)
    {
        $context = [
            'table' => $table,
            'database' => $databaseName,
            'type' => 'table_exists_in_' . $type
        ];

        if (!in_array($table, $otherTables)) {
            $this->output->write(
                'Table "' . $table . '" was found in "' . $databaseName . '" but not in "' . $secondaryDatabaseName . '".',
                array_merge($context, [
                    'result' => false
                ])
            );
        } else {
            $this->output->write(null, array_merge($context, [
                'result' => true
            ]));
        }
    }
}
