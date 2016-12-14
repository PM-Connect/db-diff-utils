<?php

namespace PMConnect\DBDiff\Utils;

use Illuminate\Console\Command;
use PMConnect\DBDiff\Utils\Contracts\Output;

class CommandOutput implements Output  {

    /**
     * @var Command
     */
    protected $console;

    public function __construct(Command $console)
    {
        $this->console = $console;
    }

    public function write(string $message = null, array $context = [])
    {
        if (!$context['result']) {
            $this->console->getOutput()->error(
                '[' . $context['database'] . '.' . $context['table'] . (isset($context['column']) ? '.' . $context['column'] : null) . '] '
                . '[' . $context['type'] . '] '
                . PHP_EOL
                . $message
            );
        }
    }
}
