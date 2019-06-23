<?php

namespace Greabock\Populator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UnitOfWork
{
    /**
     * @var Model[]
     */
    protected $toPersist = [];

    /**
     * @var Model[]
     */
    protected $toDestroy = [];

    /**
     * @var callable[]|array
     */
    protected $instructions = [];

    /**
     * @var IdentityMap
     */
    private $map;

    public function __construct(IdentityMap $map)
    {
        $this->map = $map;
    }

    public function persist(Model $model): void
    {
        $hashName = $this->map->resolveHashName($model);
        $this->toPersist[$hashName] = $model;
        if (isset($this->toDestroy[$hashName])) {
            unset($this->toDestroy[$hashName]);
        }
        $this->map[$hashName] = $model;
    }

    public function destroy(Model $model): void
    {
        $hashName = $this->map->resolveHashName($model);
        if (isset($this->toPersist[$hashName])) {
            unset($this->toPersist[$hashName]);
        }
        $this->toDestroy[$hashName] = $model;
        $this->map->forget($hashName);
    }

    public function flush(): void
    {
        DB::beginTransaction();
        try {
            $this->doPersist();
            $this->doDestroy();
            $this->doExecute();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function doPersist(): void
    {
        foreach ($this->toPersist as $model) {
            if ($model->isDirty() || !$model->exists) {
                $model->save();
            }
        }

        $this->toPersist = [];
    }

    protected function doDestroy(): void
    {
        foreach ($this->toDestroy as $model) {
            if ($model->exists) {
                $model->delete();
            }
        }
        $this->toPersist = [];
    }

    public function execute(callable $fn): void
    {
        $this->instructions[] = $fn;
    }

    private function doExecute(): void
    {
        foreach ($this->instructions as $instruction) {
            $instruction();
        }
    }
}