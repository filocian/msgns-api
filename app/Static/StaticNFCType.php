<?php

namespace App\Static;

class StaticNFCType
{
    public const YT_SUBSCRIBE_STICKER = 'youtube-subscribe-sticker';
    public const FB_STICKER = 'facebook-sticker';
    public const TT_STICKER = 'tiktok-sticker';
    public const GR_STICKER = 'google-review-sticker';

    public const IG_STICKER = 'instagram-sticker';
    public const IG_STICKER_ROUND = 'instagram-sticker-round';
    public const IG_STICKER_SQUARE = 'instagram-sticker-square';

    public const WS_STICKER = 'whatsapp-sticker';
    public const WS_STICKER_SQUARE = 'whatsapp-sticker-square';
    public const WS_STICKER_WHITESQUARE = 'whatsapp-sticker-whitesquare';


    public static function all()
    {
        return [
            self::YT_SUBSCRIBE_STICKER,
            self::FB_STICKER,
            self::TT_STICKER,
            self::GR_STICKER,
            self::IG_STICKER,
            self::IG_STICKER_ROUND,
            self::IG_STICKER_SQUARE,
            self::WS_STICKER,
            self::WS_STICKER_SQUARE,
            self::WS_STICKER_WHITESQUARE,
        ];
    }
}
