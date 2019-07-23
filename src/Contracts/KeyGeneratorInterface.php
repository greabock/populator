<?php

namespace Greabock\Populator\Contracts;

use Illuminate\Database\Eloquent\Model;

interface KeyGeneratorInterface
{
    /**  @return mixed */
    public function generate(Model $model);
}