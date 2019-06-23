<?php

namespace Greabock\Populator;


use Exception;
use Greabock\Populator\Relation\BelongsToManyPopulator;
use Greabock\Populator\Relation\BelongsToPopulator;
use Greabock\Populator\Relation\HasManyPopulator;
use Greabock\Populator\Relation\HasOnePopulator;
use Greabock\Populator\Relation\RelationPopulator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class Populator
{
    /**
     * @var Resolver
     */
    private $resolver;

    /**
     * @var RelationPopulator[]
     */
    protected $relationPopulators = [];
    /**
     * @var UnitOfWork
     */
    private $uow;

    /**
     * Populator constructor.
     * @param Resolver $resolver
     * @param UnitOfWork $uow
     */
    public function __construct(Resolver $resolver, UnitOfWork $uow)
    {
        $this->resolver = $resolver;
        $this->uow = $uow;
        $this->initRelationPopulators();
    }

    /**
     * @param mixed|Model|string $model
     * @param array|null $data
     * @return Model|null
     */
    public function populate($model, ?array $data): ?Model
    {
        assert(is_subclass_of($model, Model::class));

        if (is_null($data)) {
            return null;
        }


        if (is_string($model)) {
            $model = $this->resolveModel($model, $data);
        }

        $model->fill($data);
        $this->fillRelations($model, $data);

        $this->uow->persist($model);

        return $model;
    }

    /**
     * @throws Exception
     */
    public function flush(): void
    {
        $this->uow->flush();
    }

    /**
     * @param $model
     * @param $data
     * @return Model
     */
    public function resolveModel(string $model, array $data): Model
    {
        return $this->resolver->resolve($model, $data);
    }

    /**
     * @param Model $model
     * @param array $data
     */
    protected function fillRelations(Model $model, array $data): void
    {
        $relations = Arr::except($data, $model->getFillable());

        foreach ($relations as $relation => $relationData) {

            $relation  = Str::camel($relation);

            if (method_exists($model, $relation)) {
                $this->populateRelation($model, $relation, $relationData);
            }
        }
    }

    /**
     * @param Model $model
     * @param string $relationName
     * @param array $relationData
     */
    protected function populateRelation(Model $model, string $relationName, array $relationData): void
    {
        $relation = $model->{$relationName}();
        foreach ($this->relationPopulators as $class => $populator) {
            if ($relation instanceof $class) {
                $populator->populate($model, $relationName, $relationData);
                break;
            }
        }
    }

    private function initRelationPopulators(): void
    {
        $this->relationPopulators = [
            HasMany::class       => new HasManyPopulator($this->resolver, $this->uow, $this),
            BelongsToMany::class => new BelongsToManyPopulator($this->resolver, $this->uow, $this),
            BelongsTo::class     => new BelongsToPopulator($this->resolver, $this->uow, $this),
            HasOne::class        => new HasOnePopulator($this->resolver, $this->uow, $this),
        ];
    }
}