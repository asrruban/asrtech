<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GenerateProductContentRequest;
use App\Services\OpenAiProductContentGenerator;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class AiProductContentController extends Controller
{
    public function __invoke(
        GenerateProductContentRequest $request,
        OpenAiProductContentGenerator $generator,
    ): JsonResponse {
        try {
            return response()->json([
                'content' => $generator->generate($request->context()),
            ]);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => ['ai' => [$exception->getMessage()]],
            ], 422);
        }
    }
}
