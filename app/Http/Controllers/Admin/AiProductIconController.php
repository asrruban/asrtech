<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GenerateProductIconRequest;
use App\Services\OpenAiProductIconGenerator;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class AiProductIconController extends Controller
{
    public function __invoke(
        GenerateProductIconRequest $request,
        OpenAiProductIconGenerator $generator,
    ): JsonResponse {
        try {
            return response()->json(
                $generator->generate($request->string('name')->toString()),
            );
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => ['ai' => [$exception->getMessage()]],
            ], 422);
        }
    }
}
