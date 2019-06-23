<?php

namespace Greabock\Populator\Relation;


use Greabock\Populator\Populator;
use Greabock\Populator\Resolver;
use Greabock\Populator\UnitOfWork;
use Illuminate\Database\Eloquent\Model;

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


    abstract function populate(Model $model, string $relationName, ?array $data): void;
}