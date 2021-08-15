<?php

namespace Greabock\Populator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UnitOfWork
{
    protected IdentityMap $toPersist;

    protected IdentityMap $toDestroy;

    /** @var array<int,callable> */
    protected array $onFlushInstructions = [];

    private IdentityMap $exists;

    public function __construct(IdentityMap $map)
    {
        $this->exists = $map;
        $this->toDestroy = new IdentityMap();
        $this->toPersist = new IdentityMap();
    }

    public function persist(Model $model): void
    {
        $this->toPersist->remember($model);
        $this->toDestroy->forget($model);
        $this->exists->remember($model);
    }

    public function destroy(Model $model): void
    {
        $this->toPersist->forget($model);
        $this->toDestroy->remember($model);
        $this->exists->forget($model);
    }

    public function flush(): void
    {
        DB::transaction(function () {
            $this->doPersist();
            $this->doDestroy();
            $this->doOnFlush();
        });
    }

    protected function doPersist(): void
    {
        foreach ($this->toPersist->models()->reverse() as $model) {
            if ($model->isDirty() || !$model->exists) {
                $model->save();
            }
        }

        $this->toPersist->clear();
    }

    protected function doDestroy(): void
    {
        foreach ($this->toDestroy->models() as $model) {
            if ($model->exists) {
                $model->delete();
            }
        }

        $this->toPersist->clear();
    }

    public function onFlush(callable $fn): void
    {
        $this->onFlushInstructions[] = $fn;
    }

    private function doOnFlush(): void
    {
        foreach ($this->onFlushInstructions as $instruction) {
            $instruction();
        }

        $this->onFlushInstructions = [];
    }
}
