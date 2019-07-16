<?php

namespace Greabock\Populator;


use Greabock\Populator\Contracts\KeyGeneratorInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class Resolver
{
    /**
     * @var IdentityMap
     */
    private $identityMap;

    /**
     * @var KeyGeneratorInterface
     */
    private $generator;

    /**
     * Resolver constructor.
     * @param IdentityMap $map
     */
    public function __construct(IdentityMap $map, KeyGeneratorInterface $generator)
    {
        $this->generator = $generator;
        $this->identityMap = $map;
    }

    /**
     * @param string|Model $model
     * @param array $data
     * @param array $data
     * @return Model
     */
    public function resolve($model, array $data): Model
    {
        $model = $this->resolveModelInstance($model);

        return $this->find($model, $data) ?? $this->build($model, $data);
    }

    protected function getCached(Model $model, array $data): ?Model
    {
        return $this->identityMap->get($this->identityMap::resolveHashName($model, $data));
    }

    protected function build(Model $model, array $data): Model
    {
        $model->{$model->getKeyName()} = $this->generator->generate($model);
        return $this->identityMap[$this->identityMap::resolveHashName($model, $data)] = $model;
    }

    protected function find(Model $model, array $data): ?Model
    {
        return isset($data[self::resolveKeyName($model)]) ?
            $this->getCached($model, $data) ?? $this->findInDataBase($model, $data) : null;
    }

    public static function resolveKeyName(Model $model)
    {
        if (method_exists($model, 'getMappedKeyName')) {
            return $model->getMappedKeyName();
        }

        return $model->getKeyName();
    }

    /**
     * @param Model $model
     * @param string $relation
     * @return mixed|Collection|Model|Model[]|null
     */
    public function loadRelation(Model $model, string $relationName)
    {
        if (!$model->relationLoaded($relationName)) {
            $model->load($relationName);
        }

        $relation = $model->getRelation($relationName);

        if (!is_null($relation)) {
            $this->identityMap->remember($relation);
        }

        return $relation;
    }

    /**
     * @param Model $model
     * @param array $data
     * @return Model|null
     */
    public function findInDataBase(Model $model, array $data): ?Model
    {
        $primaryKeyName = self::resolveKeyName($model);

        if (isset($data[$primaryKeyName])) {
            $resultModel = $model->newQuery()->find($data[$primaryKeyName]);
            if ($resultModel instanceof Model) {
                $this->identityMap->remember($resultModel);
                return $resultModel;
            }
        }

        return null;
    }

    /**
     * @param mixed|Model|string $model
     * @return Model
     * @throws InvalidArgumentException
     */
    protected function resolveModelInstance($model): Model
    {
        switch (true) {
            case is_object($model) && $model instanceof Model:
                return $model;
            case is_string($model) && is_subclass_of($model, Model::class):
                /**
                 * @psalm-var class-string $model
                 * @psalm-suppress LessSpecificReturnStatement
                 */
                return new $model;
            default:
                throw new InvalidArgumentException('Argument $model should be instance or subclass of ' . Model::class);
        }
    }
}