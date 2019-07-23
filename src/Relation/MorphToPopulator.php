<?php

namespace Greabock\Populator\Relation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;

class MorphToPopulator extends RelationPopulator
{
    /**
     * @param Model $model
     * @param Relation|MorphTo $relation
     * @param array|null $data
     * @param string $relationName
     * @throws \Exception
     */
    public function populate(Model $model, Relation $relation, ?array $data, string $relationName): void
    {
        $related = $this->extractRelated($model, $relation, $data);

        $this->fillRelationField($model, $relation, $related);

        $model->setRelation(Str::snake($relation->getRelationName()), $related);
    }

    private function fillRelationField(Model $model, MorphTo $relation, ?Model $related): void
    {
        $model->{$relation->getForeignKeyName()} = $related ? $related->{$relation->getOwnerKeyName()} : null;
    }

    /**
     * @param Model $model
     * @param MorphTo $relation
     * @param array|null $data
     * @return Model
     * @throws \Exception
     */
    private function extractRelated(Model $model, MorphTo $relation, ?array $data): ?Model
    {
        $relatedModelName = $model->getAttribute($relation->getMorphType());

        if (!class_exists($relatedModelName)) {
            $relatedModelName = Relation::getMorphedModel($relatedModelName);
        }

        if (is_null($relatedModelName)) {
            throw new \Exception(sprintf('You should provide morphed model name or alias for [%s] field [%s]', get_class($model), $relation->getMorphType()));
        }

        return $this->resolver->find($relatedModelName, $data);
    }
}