<?php

namespace App\DTO;

use App\Infrastructure\Contracts\DTO;
use App\Models\NFC;

class NFCDto implements DTO
{
    public int $id;
    public string $user_id;
    public string $type;
    public ?string $qr_url;
    public ?string $target_url;

    /**
     * @param array(id:int, user_id:string, type:string, qr_url:string, target_url:string) $data
     */
    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->user_id = $data['user_id'];
        $this->type = $data['type'];
        $this->qr_url = $data['qr_url'];
        $this->target_url = $data['target_url'];
    }

    /**
     * @param NFC $model ;
     */
    public static function fromModel($model): DTO
    {
        return new NFCDto([
            'id' => $model->id,
            'user_id' => $model->user_id,
            'type' => $model->type,
            'qr_url' => $model->qr_url,
            'target_url' => $model->target_url,
        ]);
    }


}
