<?php

namespace PMConnect\DBDiff\Utils\Console\Commands;

use PMConnect\DBDiff\Utils\CommandOutput;
use Illuminate\Console\Command;
use Illuminate\Database\Connectors\ConnectionFactory;
use PMConnect\DBDiff\Utils\Diff;

class DiffDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:diff
                            {--default-collation=utf8mb4_general_ci : The collation to use by default for all connections.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run a command line based db diff.';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @param ConnectionFactory $connectionFactory
     * @return mixed
     */
    public function handle(ConnectionFactory $connectionFactory)
    {
        $this->output->section('Primary Database Details');

        $primary = $this->collectDbInfo('Database 1');

        $this->output->section('Secondary Database Details');

        $secondary = $this->collectDbInfo('Database 2');

        $output = new CommandOutput($this);

        $database1Connection = $connectionFactory->make($primary, $primary['name']);
        $database2Connection = $connectionFactory->make($secondary, $secondary['name']);

        $diffRunner = new Diff(
            $output,
            $database1Connection,
            $database2Connection
        );

        $diffRunner->diff();
    }

    /**
     * Collect the required database connection info.
     *
     * @param null $name
     * @return array
     */
    protected function collectDbInfo($name = null)
    {
        $name = $this->ask('Enter a name for the connection', $name);
        $driver = $this->choice('Select a database driver', ['mysql', 'pgsql'], 0);
        $host = $this->ask('Enter the host to connect to', '127.0.0.1');
        $port = $this->ask('Enter the port to connect on', 3306);
        $database = $this->ask('Enter the database to diff', $name);
        $username = $this->ask('Enter the username to connect with', $database);
        $password = $this->secret('Enter the password to connect with');
        $collation = $this->ask('Enter the collation of the database', $this->option('default-collation'));
        return [
            'name' => $name,
            'driver' => $driver,
            'host' => $host,
            'port' => $port,
            'database' => $database,
            'username' => $username,
            'password' => $password,
            'collation' => $collation,
        ];
    }
}
