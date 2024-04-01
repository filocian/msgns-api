<?php

namespace App\Repositories;

use App\DTO\NFCDto;
use App\Exceptions\NFC\NFCNotFoundException;
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

    /**
     * @throws NFCNotFoundException
     */
    public function findOne(string $id, ?array $opts = null): NFCDto
    {
        try {
            $found = NFC::query()->with($opts['include'] ?? [])->findOrFail($id);

            return NFCDto::fromModel($found, $opts);
        } catch (ModelNotFoundException $e) {
            throw new NFCNotFoundException();
        }
    }

    /**
     * @throws NFCNotFoundException
     */
    public function findOneBy(array $attributes, ?array $opts = null): NFCDto
    {
        try {
            $found = NFC::query()->where($attributes)->first();

            if (!($found instanceof NFC)) {
                throw new NFCNotFoundException();
            }

            return NFCDto::fromModel($found, $opts);
        } catch (ModelNotFoundException $e) {
            throw new NFCNotFoundException();
        }
    }

    /**
     * @param array<string, mixed> $attributes
     * @return NFCDto[]
     */
    public function findBy(array $attributes, ?array $opts = null): array
    {
        $found = NFC::query()->with(['type'])->where($attributes)->get();
        return NFCDto::fromModelCollection($found, $opts);
    }


    public function create(array $data, ?array $opts = null): NFCDto
    {
        return NFCDto::fromModel(
            NFC::query()
                ->create($data)
                ->firstOrFail(),
            $opts
        );
    }

    /**
     * @throws NFCNotFoundException
     */
    public function update(string $id, $data, ?array $opts = null): NFCDto
    {
        try {

            NFC::query()
                ->where('id', $id)
                ->update($data);

            return $this->findOne($id, $opts);

        } catch (ModelNotFoundException $e) {
            throw new NFCNotFoundException();
        }
    }

    public function delete($id)
    {
        return NFC::query()
            ->where('id', $id)
            ->delete();
    }
}
