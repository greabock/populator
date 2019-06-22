<?php

namespace Greabock\Populator;


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

    public function __construct(
        Resolver $resolver,
        UnitOfWork $uow,
        HasManyPopulator $hasManyPopulator,
        BelongsToManyPopulator $belongsToManyPopulator,
        BelongsToPopulator $belongsToPopulator,
        HasOnePopulator $populator
    )
    {
        $this->resolver = $resolver;
        $this->relationPopulators = [
            HasMany::class       => $hasManyPopulator,
            BelongsToMany::class => $belongsToManyPopulator,
            BelongsTo::class     => $belongsToPopulator,
            HasOne::class        => $populator,
        ];
        $this->uow = $uow;

        foreach ($this->relationPopulators as $relationPopulator) {
            $relationPopulator->setModelPopulator($this);
        }
    }

    public function populate($model, array $data)
    {
        assert(class_exists($model) || $model instanceof Model);

        if (!$data) {
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

    public function resolveModel($model, $data)
    {
        return $this->resolver->resolve($model, $data);
    }

    protected function fillRelations(Model $model, array $data)
    {
        $relations = Arr::except($data, $model->getFillable());

        foreach ($relations as $relation => $relationData) {
            if (method_exists($model, $relation)) {
                $this->populateRelation($model, $relation, $relationData);
            }
        }
    }

    protected function populateRelation(Model $model, string $relationName, array $relationData)
    {

        $relation = $model->{$relationName}();
        foreach ($this->relationPopulators as $class => $populator) {
            if ($relation instanceof $class) {
                $populator->populate($model, $relationName, $relationData);
                break;
            }
        }
    }
}