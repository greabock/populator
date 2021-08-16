<?php

namespace Tests;

use Greabock\Populator\IdentityMap;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class IdentityMapTest extends TestCase
{
    public function testImCanRememberModels()
    {
        $im = new IdentityMap();

        $model = $this->mockModel();

        $im->remember($model);

        $this->assertSame($model, $im->get(clone $model, [$model->getKeyName() => $model->getKey()]));
        $this->assertSame($model, $im->models()->first());
        $this->assertSame($model, $im->models()->last());
        $this->assertNull($im->get(clone $model, [$model->getKeyName() => 'otherId']));
    }

    public function testImCanForgetModels()
    {
        $im = new IdentityMap();

        $model = $this->mockModel();

        $im->remember($model);

        $im->forget($model);

        $this->assertNull($im->get(clone $model, [$model->getKeyName() => $model->getKey()]));
        $this->assertNull($im->models()->first());
        $this->assertNull($im->models()->last());
    }

    public function testImCanTrackRelations()
    {
        $im = new IdentityMap();

        $another = $this->mockModel();

        $others = new EloquentCollection([$this->mockModel()]);
        $model = $this->mockModel(compact('another', 'others'));

        $im->track($model);

        $this->assertSame($another, $im->get(clone $another, [$another->getKeyName() => $another->getKey()]));
        $this->assertSame($model, $im->get(clone $model, [$model->getKeyName() => $model->getKey()]));

        foreach ($others as $other) {
            $this->assertSame($other, $im->get(clone $other, [$other->getKeyName() => $other->getKey()]));
        }
    }

    private function mockModel(array $relations = []): Model
    {
        $model = $this->createMock(Model::class);

        $model->method('getKey')->willReturn(Uuid::uuid4()->toString());

        $model->method('getRelations')->willReturn($relations);

        return $model;
    }
}