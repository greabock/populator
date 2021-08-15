<?php

namespace Tests;

use Greabock\Populator\IdentityMap;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

class IdentityMapTest extends TestCase
{
    public function testImCanRememberModels()
    {
        $im = new IdentityMap();

        $model = $this->createMock(Model::class);

        $model->method('getKey')
            ->willReturn('testId');

        $im->remember($model);

        $this->assertSame($model, $im->get($model, [$model->getKeyName() => $model->getKey()]));
        $this->assertSame($model, $im->models()->first());
        $this->assertSame($model, $im->models()->last());
        $this->assertNull($im->get($model, [$model->getKeyName() => 'otherId']));
    }

    public function testImCanForgetModels()
    {
        $im = new IdentityMap();

        $model = $this->createMock(Model::class);

        $model->method('getKey')
            ->willReturn('testId');

        $im->remember($model);
        $im->forget($model);

        $this->assertNull($im->get($model, [$model->getKeyName() => $model->getKey()]));
        $this->assertNull($im->models()->first());
        $this->assertNull($im->models()->last());
    }
}