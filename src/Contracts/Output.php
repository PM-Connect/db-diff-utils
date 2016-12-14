<?php

namespace PMConnect\DBDiff\Utils\Contracts;

interface Output
{
    /**
     * Write the given message and context.
     *
     * @param string|null $message
     * @param array $context
     */
    public function write(string $message = null, array $context = []);
}
