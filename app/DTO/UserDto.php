<?php

namespace App\DTO;

use App\Infrastructure\Contracts\DTO;
use App\Models\User;
use Illuminate\Support\Collection;

class UserDto implements DTO
{
    public int $id;
    public string $name;
    public string $email;
    public string $created_at;
    public string $updated_at;

    /**
     * @param array $data (id:int, name:string, email:string, created_at: ?string, updated_at: ?string)
     */
    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->name = $data['name'];
        $this->email = $data['email'];

        if (isset($data['updated_at'])) {
            $this->updated_at = $data['updated_at'];
        }

        if (isset($data['created_at'])) {
            $this->created_at = $data['created_at'];
        }
    }

    /**
     * @param User $model ;
     */
    public static function fromModel($model, ?array $opts = null): UserDto
    {
        return new UserDto([
            'id' => $model->id,
            'name' => $model->name,
            'email' => $model->email,
        ]);
    }

    public static function fromModelCollection(array|Collection $models, ?array $opts = null): array
    {
        $data = $models instanceof Collection ? $models->all() : $models;

        return array_map(function ($model) {
            return UserDto::fromModel($model);
        }, $data);
    }


}
