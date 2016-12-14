<?php

namespace PMConnect\DBDiff\Utils;

use Illuminate\Database\Connection;
use PMConnect\DBDiff\Utils\Contracts\Diff as DiffContract;
use PMConnect\DBDiff\Utils\Contracts\Output as OutputContract;

class Diff implements DiffContract
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

    /**
     * @var array
     */
    protected $diffs = [
        TableNames::class,
        TableStructure::class,
    ];

    public function __construct(OutputContract $output, Connection $connection1, Connection $connection2)
    {
        $this->output = $output;
        $this->db1 = $connection1;
        $this->db2 = $connection2;
    }

    public function diff()
    {
        foreach ($this->diffs as $diff) {
            $task = new $diff($this->output, $this->db1, $this->db2);
            $task->diff();
        }
    }
}
