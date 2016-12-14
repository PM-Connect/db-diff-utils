<?php

namespace spec\PMConnect\DBDiff\Utils;

use PMConnect\DBDiff\Utils\Contracts\Output as OutputContract;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class OutputSpec extends ObjectBehavior
{
    function let()
    {
        $this->beAnInstanceOf(OutputFake::class);
        $this->beConstructedWith();
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(OutputFake::class);
    }

    function it_implements_output_contract()
    {
        $this->shouldImplement(OutputContract::class);
    }

    function it_writes_message()
    {
        $this->write('Message')->shouldReturn('Message');
    }
}

class OutputFake implements OutputContract
{
    public function write(string $message = null, array $context = [])
    {
        return $message;
    }
}
