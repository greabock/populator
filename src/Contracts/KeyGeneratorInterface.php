<?php

namespace Greabock\Populator\Contracts;

use Illuminate\Database\Eloquent\Model;

interface KeyGeneratorInterface
{
    public function generate(Model $model): mixed;
}