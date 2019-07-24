<?php

namespace Greabock\Populator\Relation;

use Greabock\Populator\Populator;
use Greabock\Populator\Resolver;
use Greabock\Populator\UnitOfWork;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

abstract class RelationPopulator
{
    /**
     * @var Resolver
     */
    protected $resolver;
    /**
     * @var UnitOfWork
     */
    protected $uow;

    /**
     * @var Populator
     */
    protected $populator;

    public function __construct(Resolver $resolver, UnitOfWork $uow, Populator $populator)
    {
        $this->resolver = $resolver;
        $this->uow = $uow;
        $this->populator = $populator;
    }

    /**
     * @param Model $model
     * @param Relation $relation
     * @param array|null $data
     * @param string $relationName
     */
    abstract public function populate(Model $model, Relation $relation, ?array $data, string $relationName): void;
}
