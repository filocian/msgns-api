<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductConfigurationStatus extends Model
{
	use HasFactory;

	protected $table = 'configuration_status_codes';
	protected $fillable = [
		'status_code',
		'description'
	];

	public static string $STATUS_NOT_STARTED = 'not-started';
	public static string $STATUS_ASSIGNED = 'assigned';
	public static string $STATUS_TARGET_SET = 'target_set';
	public static string $STATUS_BUSINESS_SET = 'business_set';
	public static string $STATUS_COMPLETED = 'completed';
}