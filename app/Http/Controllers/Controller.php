<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * @OA\Info(title="APP API", version="1.0")
 */
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * @return int
     */
    protected function perPage()
    {
        return request()->get('perPage') ?? config('settings.pagination.perPage');
    }

    /**
     * @param $data
     * @param int $responseCode
     * @return \Illuminate\Http\JsonResponse
     */
    protected function sendResponse($data, $responseCode = 200)
    {
        if ($data instanceof ResourceCollection &&
            isset($data->resource) &&
            $data->resource instanceof LengthAwarePaginator) {
            return $data;
        }
        return response()->json([
            'data' => $data
        ], $responseCode);
    }
}
