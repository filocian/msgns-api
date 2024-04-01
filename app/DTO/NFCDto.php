<?php

namespace App\DTO;

use App\Infrastructure\Contracts\DTO;
use App\Models\NFC;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class NFCDto implements DTO
{
    public int $id;
    public ?int $user_id;
    public ?string $target_url;
    public ProductTypeDto $type;
    public ?UserDto $user = null;

    /**
     * @param array $data (id:int, user_id:int, qr_url:string, target_url:string, user: UserDto, type: ProductTypeDto)
     */
    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->user_id = $data['user_id'];
        $this->target_url = $data['target_url'];
        $this->type = $data['type'] ?? null;
        if (isset($data['user'])) {
            $this->user = $data['user'] ?? null;
        }
    }

    /**
     * @param Model|NFC $model ;
     */
    public static function fromModel($model, ?array $opts = null): NFCDto
    {
        $data = [
            'id' => $model->id,
            'user_id' => $model->user_id,
            'target_url' => $model->target_url,
            'type' => ProductTypeDto::fromModel($model->type)
        ];

        if (boolval($opts) && isset($opts['include'])) {
            if (in_array('user', $opts['include'])) {
                $data['user'] = UserDto::fromModel($model->user);
            }
        }

        return new NFCDto($data);
    }


    /**
     * @param array<NFC>|Collection $models
     * @return array<NFCDto>
     */
    public static function fromModelCollection(array|Collection $models, ?array $opts = null): array
    {
        $data = $models instanceof Collection ? $models->all() : $models;

        return array_map(function ($model) use ($opts) {
            return NFCDto::fromModel($model, $opts ?? null);
        }, $data);
    }


}
