<?php

namespace App\Static;

class StaticProductType {
    public const NFC = 'nfc';

    public function all()
    {
        return [
            StaticProductType::NFC
        ];
    }
}

