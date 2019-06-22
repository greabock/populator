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

    public function __construct(Resolver $resolver, UnitOfWork $uow)
    {
        $this->resolver = $resolver;
        $this->uow = $uow;
    }

    /**
     * @var Populator
     */
    protected $populator;

    abstract function populate(Model $model, string $relationName, ?array $data);

    public function setModelPopulator(Populator $populator)
    {
        $this->populator = $populator;
    }
}