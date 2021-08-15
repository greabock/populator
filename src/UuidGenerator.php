<?php

namespace Greabock\Populator;

use Greabock\Populator\Contracts\KeyGeneratorInterface;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;

class UuidGenerator implements KeyGeneratorInterface
{
    public function generate(Model $model): string
    {
        return Uuid::uuid4()->toString();
    }
}