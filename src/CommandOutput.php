<?php

namespace PMConnect\DBDiff\Utils;

use Illuminate\Console\Command;
use PMConnect\DBDiff\Utils\Contracts\Output;

/**
 * Class CommandOutput
 *
 * Output the results of the diff to the console.
 *
 * @package PMConnect\DBDiff\Utils
 */
class CommandOutput implements Output  {

    /**
     * @var Command
     */
    protected $console;

    public function __construct(Command $console)
    {
        $this->console = $console;
    }

    /**
     * Write the given message and context.
     *
     * @param string|null $message
     * @param array $context
     */
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
