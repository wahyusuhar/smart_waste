<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ClassifyRequest;
use App\Services\WasteClassifier;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassifyController extends Controller
{
    // A ClassifierException thrown here renders itself as a 502/503 JSON response.
    public function __invoke(ClassifyRequest $request, WasteClassifier $classifier): JsonResource
    {
        return new JsonResource($classifier->classify($request->file('photo')));
    }
}
