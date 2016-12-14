<?php

namespace spec\PMConnect\DBDiff\Utils;

use PMConnect\DBDiff\Utils\Contracts\Diff as DiffContract;
use PMConnect\DBDiff\Utils\Contracts\Output;
use PMConnect\DBDiff\Utils\Diff;
use Illuminate\Database\Connection;
use PhpSpec\ObjectBehavior;

class DiffSpec extends ObjectBehavior
{
    function let(Output $output, Connection $connection1, Connection $connection2)
    {
        $this->beConstructedWith($output, $connection1, $connection2);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(Diff::class);
    }

    function it_should_implement_diff_contract()
    {
        $this->shouldBeAnInstanceOf(DiffContract::class);
    }
}
