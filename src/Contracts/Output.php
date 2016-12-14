<?php

namespace PMConnect\DBDiff\Utils\Contracts;

interface Output
{
    public function write(string $message = null, array $context = []);
}
