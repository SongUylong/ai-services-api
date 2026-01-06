<?php

namespace App\Http\Controllers\Api\v1\AiModels;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\AiModels\AiModelResource;
use App\Models\AiModels\AiModel;

class AiModelController extends ApiController
{
    // List all available AI models
    public function index()
    {
        $aiModels = AiModel::all();

        return $this->okWithData(AiModelResource::collection($aiModels));
    }
}
