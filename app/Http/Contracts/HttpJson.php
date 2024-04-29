<?php

declare(strict_types=1);

namespace App\Http\Contracts;

//use App\DTO\PaginatedResponseDTO;
use Illuminate\Http\Response;

final class HttpJson
{
	public static function OK(mixed $data, int $status = 200)
	{
		return response()->json([
			'data' => $data,
		], $status);
	}

	//	public static function OKPaginated(PaginatedResponseDTO $data, string $dataName, int $status = 200)
	//	{
	//		return response()->json([
	//			$dataName => $data->data,
	//			'pagination' => $data->pagination
	//		], $status);
	//	}

	public static function KO(string $message, int $status = 500, array $extra = [])
	{
		return response()->json([
			'error' => [
				'message' => $message,
				...$extra,
			],
		], $status);
	}

	public static function response(): Response
	{
		return response();
	}
}
