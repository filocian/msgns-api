<?php

namespace App\Repositories;

use App\Infrastructure\Contracts\RepositoryContract;
use App\Models\NFC;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class NFCRepository implements RepositoryContract
{

    public function getAll()
    {
        return NFC::all();
    }

    public function query(callable $customQuery)
    {
        return $customQuery(NFC::query());
    }

    public function findOne($id)
    {
        $found = NFC::query()->where('id', $id)->first();
        if (!boolval($found)) {
            throw new ModelNotFoundException('nfc_not_found');
        }

        return $found;
    }

    public function create($data)
    {
        return NFC::query()
            ->create($data);
    }

    public function update($id, $data)
    {
        $id = NFC::query()
            ->where('id', $id)
            ->update($data);


        return $this->findOne($id);
    }

    public function delete($id)
    {
        return NFC::query()
            ->where('id', $id)
            ->delete();
    }
}
