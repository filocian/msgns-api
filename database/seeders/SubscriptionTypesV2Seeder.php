<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Src\Identity\Domain\Permissions\DomainRoles;

final class SubscriptionTypesV2Seeder extends Seeder
{
    public function run(): void
    {
        $now = now()->toDateTimeString();

        $types = [
            [
                'name'                    => 'Google Review Basic',
                'slug'                    => 'google-review-basic',
                'description'             => null,
                'mode'                    => 'classic',
                'billing_periods'         => json_encode(['monthly', 'annual']),
                'base_price_cents'        => 200,
                'permission_name'         => 'ai.google-review-basic',
                'google_review_limit'     => 50,
                'instagram_content_limit' => 0,
                'stripe_product_id'       => null,
                'stripe_price_ids'        => null,
                'is_active'               => true,
                'created_at'              => $now,
                'updated_at'              => $now,
            ],
            [
                'name'                    => 'Google Review Pro',
                'slug'                    => 'google-review-pro',
                'description'             => null,
                'mode'                    => 'classic',
                'billing_periods'         => json_encode(['monthly', 'annual']),
                'base_price_cents'        => 500,
                'permission_name'         => 'ai.google-review-pro',
                'google_review_limit'     => 200,
                'instagram_content_limit' => 0,
                'stripe_product_id'       => null,
                'stripe_price_ids'        => null,
                'is_active'               => true,
                'created_at'              => $now,
                'updated_at'              => $now,
            ],
            [
                'name'                    => 'Instagram Content Basic',
                'slug'                    => 'instagram-content-basic',
                'description'             => null,
                'mode'                    => 'classic',
                'billing_periods'         => json_encode(['monthly', 'annual']),
                'base_price_cents'        => 200,
                'permission_name'         => 'ai.instagram-content-basic',
                'google_review_limit'     => 0,
                'instagram_content_limit' => 20,
                'stripe_product_id'       => null,
                'stripe_price_ids'        => null,
                'is_active'               => true,
                'created_at'              => $now,
                'updated_at'              => $now,
            ],
            [
                'name'                    => 'Instagram Content Pro',
                'slug'                    => 'instagram-content-pro',
                'description'             => null,
                'mode'                    => 'classic',
                'billing_periods'         => json_encode(['monthly', 'annual']),
                'base_price_cents'        => 500,
                'permission_name'         => 'ai.instagram-content-pro',
                'google_review_limit'     => 0,
                'instagram_content_limit' => 80,
                'stripe_product_id'       => null,
                'stripe_price_ids'        => null,
                'is_active'               => true,
                'created_at'              => $now,
                'updated_at'              => $now,
            ],
            [
                'name'                    => 'IA Bundle Basic',
                'slug'                    => 'ia-bundle-basic',
                'description'             => null,
                'mode'                    => 'classic',
                'billing_periods'         => json_encode(['monthly', 'annual']),
                'base_price_cents'        => 500,
                'permission_name'         => 'ai.bundle-basic',
                'google_review_limit'     => 40,
                'instagram_content_limit' => 15,
                'stripe_product_id'       => null,
                'stripe_price_ids'        => null,
                'is_active'               => true,
                'created_at'              => $now,
                'updated_at'              => $now,
            ],
            [
                'name'                    => 'IA Bundle Pro',
                'slug'                    => 'ia-bundle-pro',
                'description'             => null,
                'mode'                    => 'classic',
                'billing_periods'         => json_encode(['monthly', 'annual']),
                'base_price_cents'        => 1000,
                'permission_name'         => 'ai.bundle-pro',
                'google_review_limit'     => 150,
                'instagram_content_limit' => 60,
                'stripe_product_id'       => null,
                'stripe_price_ids'        => null,
                'is_active'               => true,
                'created_at'              => $now,
                'updated_at'              => $now,
            ],
            [
                'name'                    => 'Google Review Prepaid',
                'slug'                    => 'google-review-prepaid',
                'description'             => null,
                'mode'                    => 'prepaid',
                'billing_periods'         => null,
                'base_price_cents'        => 400,
                'permission_name'         => 'ai.google-review-prepaid',
                'google_review_limit'     => 50,
                'instagram_content_limit' => 0,
                'stripe_product_id'       => null,
                'stripe_price_ids'        => null,
                'is_active'               => true,
                'created_at'              => $now,
                'updated_at'              => $now,
            ],
            [
                'name'                    => 'Instagram Content Prepaid',
                'slug'                    => 'instagram-content-prepaid',
                'description'             => null,
                'mode'                    => 'prepaid',
                'billing_periods'         => null,
                'base_price_cents'        => 400,
                'permission_name'         => 'ai.instagram-content-prepaid',
                'google_review_limit'     => 0,
                'instagram_content_limit' => 20,
                'stripe_product_id'       => null,
                'stripe_price_ids'        => null,
                'is_active'               => true,
                'created_at'              => $now,
                'updated_at'              => $now,
            ],
        ];

        DB::table('subscription_types')->upsert(
            $types,
            ['slug'],
            ['name', 'description', 'mode', 'billing_periods', 'base_price_cents', 'permission_name', 'google_review_limit', 'instagram_content_limit', 'stripe_product_id', 'stripe_price_ids', 'is_active', 'updated_at'],
        );

        foreach ($types as $type) {
            Permission::findOrCreate($type['permission_name'], DomainRoles::GUARD);
        }
    }
}
