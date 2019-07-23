<?php

namespace Greabock\Populator\Contracts;

use Illuminate\Database\Eloquent\Model;

interface KeyGeneratorInterface
{
    /**
     * @param Model $model
     * @return mixed
     */
    public function generate(Model $model);
}