<?php

namespace Tests;

use Greabock\Populator\Contracts\KeyGeneratorInterface;
use Greabock\Populator\IdentityMap;
use Greabock\Populator\Resolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\MockObject\MockBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class ResolverTest extends TestCase
{
    public function testResolverCanResolveEntityFromIm(): void
    {
        $model = $this->mockModel();

        $resolver = new Resolver(
            $this->getKeyGenerator(),
            $this->getIm($model),
        );

        $this->assertSame($model, $resolver->find(clone $model, [$model->getKeyName() => $model->getKey()]));
    }

    public function testResolverCanResolveEntityFromDatabase(): void
    {
        $model = $this->getMockBuilder(Model::class)->getMock();
        $queryMock = $this->createMock(Builder::class);
        $queryMock->expects($this->once())->method('find')->willReturn($model);
        $model->expects($this->once())->method('newQuery')->willReturn($queryMock);
        $model->setAttribute($model->getKeyName(), 'testID');

        $resolver = new Resolver(
            $this->getKeyGenerator(),
            $this->getIm(null),
        );

        $result = $resolver->find(clone $model, [$model->getKeyName() => 'testID']);

        $this->assertSame($model, $result);
    }

    public function getKeyGenerator(): KeyGeneratorInterface
    {
        $generator = $this->createMock(KeyGeneratorInterface::class);
        $generator->method('generate')->willReturnCallback(function () {
            return Uuid::uuid4()->toString();
        });

        return $generator;
    }


    public function getIm($model): IdentityMap
    {
        $im = $this->createMock(IdentityMap::class);
        $im->method('remember');
        $im->method('forget');
        $im->method('track');
        $im->method('get')->willReturnCallback(fn() => $model);

        return $im;
    }

    private function mockModel(array $relations = []): Model|MockObject
    {

        $model = $this->createMock(Model::class);

        $model->expects($this->once())->method('getKey')->willReturn(Uuid::uuid4()->toString());

        $model->expects($this->once())->method('getRelations')->willReturn($relations);

        return $model;
    }
}