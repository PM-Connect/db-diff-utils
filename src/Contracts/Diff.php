<?php

namespace PMConnect\DBDiff\Utils\Contracts;

interface Diff
{
    /**
     * Run the diff.
     *
     * @return mixed
     */
    public function diff();
}
