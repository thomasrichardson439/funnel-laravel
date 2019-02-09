<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Services\CategoryService;
use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Rules\Uuid;

class CategoriesController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/categories",
     *     summary="Returns all categories.",
     *     @OA\Response(response="200", description="List of all categories")
     *
     * )
     * @param CategoryService $CategoryService
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(CategoryService $CategoryService)
    {
        $results = $CategoryService->getActive();
        foreach ($results as $key => $result) {
        	if(trim($result->icon) !== '') {
	        	$result->icon = asset('storage/' . $result->icon);
	        }
        }
        return CategoryResource::collection($results);
    }
}
