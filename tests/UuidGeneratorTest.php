<?php

namespace Tests;

use Greabock\Populator\UuidGenerator;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class UuidGeneratorTest extends TestCase
{
    public function testGeneratorGenerateValidUuid()
    {
        $generator = new UuidGenerator();

        $key = $generator->generate(
            $this->createConfiguredMock(Model::class, [])
        );

        $this->assertTrue(Uuid::isValid($key));
    }
}