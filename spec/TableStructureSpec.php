<?php

namespace spec\PMConnect\DBDiff\Utils;

use PMConnect\DBDiff\Utils\Contracts\Diff;
use PMConnect\DBDiff\Utils\Contracts\Output;
use PMConnect\DBDiff\Utils\TableStructure;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Illuminate\Database\Connection;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class TableStructureSpec extends ObjectBehavior
{
    function let(Output $output, Connection $connection1, Connection $connection2)
    {
        $this->beConstructedWith($output, $connection1, $connection2);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(TableStructure::class);
    }

    function it_implements_diff_contract()
    {
        $this->shouldImplement(Diff::class);
    }

    function it_should_diff_table_structures(
        Output $output,
        Connection $connection1,
        Connection $connection2,
        AbstractSchemaManager $schema1,
        AbstractSchemaManager $schema2,
        Table $table
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

        $schema1->listTableDetails(Argument::type('string'))->shouldBeCalled()->willReturn($table);
        $schema2->listTableDetails(Argument::type('string'))->shouldBeCalled()->willReturn($table);

        $table->getName()->shouldBeCalled()->willReturn('test');
        $table->getColumns()->shouldBeCalled()->willReturn([]);
        $table->getIndexes()->shouldBeCalled()->willReturn([]);
        $table->getForeignKeys()->shouldBeCalled()->willReturn([]);

        $this->diff();
    }
}
