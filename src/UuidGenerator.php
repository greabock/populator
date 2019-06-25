<?php

namespace Greabock\Populator;

use Greabock\Populator\Contracts\KeyGeneratorInterface;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;


class UuidGenerator implements KeyGeneratorInterface
{
    /**
     * @param Model $model
     * @return mixed
     * @throws \Exception
     */
    public function generate(Model $model)
    {
        return Uuid::uuid4()->toString();
    }
}