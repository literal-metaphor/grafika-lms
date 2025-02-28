<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;

class GenericCrudService
{
    protected $model;

    public function __construct(
        Model $model
    ) {
        $this->model = $model;
    }

    /**
     * Display a listing of the resource.
     * @param int $page
     * @param int $size
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function index(int $page = null, int $size = null, array|string $relations = [])
    {
        $models = $this->model->with($relations)->paginate(
            $size,
            ['*'],
            'page',
            $page
        );

        return $models;
    }

    /**
     * Store a newly created resource in storage.
     * @param array $data
     * @param array $rules
     * @return Model
     */
    public function store(array $data, array $rules)
    {
        $data = validator($data, $rules)->validate();

        $model = $this->model->create($data);
        return $model;
    }

    /**
     * Display the specified resource.
     * @param string $id
     * @return Model
     */
    public function show(string $id, array|string $relations = [])
    {
        return $this->model->with($relations)->findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     * @param string $id
     * @param array $data
     * @param array $rules
     * @return Model
     */
    public function update(string $id, array $data, array $rules)
    {
        /** @var Model $model */
        $model = $this->model->findOrFail($id);
        $data = validator($data, $rules)->validate();

        $model->update($data);

        return $model;
    }

    /**
     * Remove the specified resource from storage.
     * @param string $id
     * @return void
     */
    public function destroy(string $id)
    {
        /** @var Model $model */
        $model = $this->model->findOrFail($id);
        $model->deleteOrFail();
    }
}
