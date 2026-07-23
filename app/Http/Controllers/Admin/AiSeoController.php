<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GenerateSeoRequest;
use App\Services\OpenAiSeoGenerator;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class AiSeoController extends Controller
{
    public function __invoke(GenerateSeoRequest $request, OpenAiSeoGenerator $generator): JsonResponse
    {
        try {
            return response()->json([
                'seo' => $generator->generate($request->context()),
            ]);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => ['ai' => [$exception->getMessage()]],
            ], 422);
        }
    }
}
