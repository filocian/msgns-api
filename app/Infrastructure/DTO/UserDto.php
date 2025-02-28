<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO;

use App\Infrastructure\Contracts\DTO\Abstract\BaseDTO;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

final class UserDto extends BaseDTO
{
	private Model $model;
	public int $id;
	public string $name;
	public string $email;
	public string|null $contact_email;
	public string|null $phone;
	public string|null $country;
	public string|null $google_id;
	public bool $password_reset_required;
	public string $default_locale;
	public mixed $roles;
	public string|null $user_agent;
	public Carbon|null $email_verified_at;
	public Carbon|null $last_access;
	public Carbon $created_at;
	public Carbon $updated_at;

	public function __construct(Model $model)
	{
		$this->model = $model;
		$this->id = $model->id;
		$this->name = $model->name;
		$this->email = $model->email;
		$this->contact_email = $model->contact_email ?? null;
		$this->phone = $model->phone ?? null;
		$this->country = $model->country ?? null;
		$this->google_id = $model->google_id;
		$this->roles = $model->getRoles($model->id);
		$this->password_reset_required = $model->password_reset_required;
		$this->default_locale = $model->default_locale;
		$this->user_agent = $model->user_agent ?? null;
		$this->email_verified_at = $model->email_verified_at;
		$this->last_access = $model->last_access ?? null;
		$this->created_at = $model->created_at;
		$this->updated_at = $model->updated_at;
	}

	public function getContactId()
	{
		return $this->model->ghlContact->contact_id ?? null;
	}
}
