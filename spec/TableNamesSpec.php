<?php

namespace spec\PMConnect\DBDiff\Utils;

use PMConnect\DBDiff\Utils\Contracts\Diff;
use PMConnect\DBDiff\Utils\Contracts\Output;
use PMConnect\DBDiff\Utils\TableNames;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Illuminate\Database\Connection;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class TableNamesSpec extends ObjectBehavior
{
    function let(Output $output, Connection $connection1, Connection $connection2)
    {
        $this->beConstructedWith($output, $connection1, $connection2);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(TableNames::class);
    }

    function it_should_implement_diff_contract()
    {
        $this->shouldImplement(Diff::class);
    }

    function it_should_run_diff(
        Output $output,
        Connection $connection1,
        Connection $connection2,
        AbstractSchemaManager $schema1,
        AbstractSchemaManager $schema2
    ) {
        $connection1->getDoctrineSchemaManager()->shouldBeCalled()->willReturn($schema1);
        $connection2->getDoctrineSchemaManager()->shouldBeCalled()->willReturn($schema2);
        $connection1->getDatabaseName()->shouldBeCalled()->willReturn('db1');
        $connection2->getDatabaseName()->shouldBeCalled()->willReturn('db2');

        $schema1->listTableNames()->shouldBeCalled()->willReturn([
            'table1',
            'table2'
        ]);

        $schema2->listTableNames()->shouldBeCalled()->willReturn([
            'table2'
        ]);

        $output->write(Argument::type('string'), [
            'database' => 'db1',
            'table' => 'table1',
            'type' => 'table_exists_in_comparison',
            'result' => false
        ])->shouldBeCalledTimes(1);

        $output->write(null, [
            'database' => 'db1',
            'table' => 'table2',
            'type' => 'table_exists_in_comparison',
            'result' => true
        ])->shouldBeCalledTimes(1);

        $output->write(null, [
            'database' => 'db2',
            'table' => 'table2',
            'type' => 'table_exists_in_primary',
            'result' => true
        ])->shouldBeCalledTimes(1);

        $this->diff();
    }
}
