<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

final class ProductConfigurationStatus extends Model
{
	use HasFactory;

	protected $table = 'configuration_status_codes';
	protected $fillable = ['status_code', 'description'];

	public static function list(): Collection
	{
		return self::get();
	}

	public static string $STATUS_NOT_STARTED = 'not-started';
	public static string $STATUS_ASSIGNED = 'assigned';
	public static string $STATUS_TARGET_SET = 'target-set';
	public static string $STATUS_BUSINESS_SET = 'business-set';
	public static string $STATUS_COMPLETED = 'completed';
}
