<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProductReviewStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ModerateProductReviewRequest;
use App\Models\Admin;
use App\Models\ProductReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProductReviewController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->string('status', ProductReviewStatus::Pending->value)->toString();
        $search = $request->string('search')->trim()->toString();

        validator(
            ['status' => $status],
            ['status' => ['nullable', Rule::enum(ProductReviewStatus::class)]],
        )->validate();

        return Inertia::render('Admin/Catalog/ProductReviews/Index', [
            'filters' => compact('status', 'search'),
            'statusOptions' => ProductReviewStatus::values(),
            'counts' => [
                'pending' => ProductReview::query()
                    ->where('status', ProductReviewStatus::Pending)
                    ->count(),
                'approved' => ProductReview::query()
                    ->where('status', ProductReviewStatus::Approved)
                    ->count(),
                'hidden' => ProductReview::query()
                    ->where('status', ProductReviewStatus::Hidden)
                    ->count(),
            ],
            'reviews' => ProductReview::query()
                ->with([
                    'user:id,name,email',
                    'product:id,name,slug,type',
                    'product.productType:id,name,key,slug',
                    'moderator:id,name',
                ])
                ->when($status, fn ($query) => $query->where('status', $status))
                ->when($search, fn ($query) => $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('content', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($query) => $query
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%"))
                        ->orWhereHas('product', fn ($query) => $query
                            ->where('name', 'like', "%{$search}%"));
                }))
                ->latest()
                ->paginate((int) config('asrtech.records_per_page', 15))
                ->withQueryString()
                ->through(fn (ProductReview $review): array => [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'title' => $review->title,
                    'content' => $review->content,
                    'status' => $review->status->value,
                    'moderation_note' => $review->moderation_note,
                    'moderated_at' => $review->moderated_at,
                    'created_at' => $review->created_at,
                    'user' => $review->user?->only(['id', 'name', 'email']),
                    'product' => [
                        'id' => $review->product->id,
                        'name' => $review->product->name,
                        'url' => $review->product->storefrontUrl(),
                    ],
                    'moderator' => $review->moderator?->only(['id', 'name']),
                ]),
        ]);
    }

    public function update(
        ModerateProductReviewRequest $request,
        ProductReview $review,
    ): RedirectResponse {
        $admin = $request->user('admin');
        abort_unless($admin instanceof Admin, 403);

        $review->update([
            ...$request->validated(),
            'moderated_by' => $admin->id,
            'moderated_at' => now(),
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Review moderation status updated.'),
        ]);

        return back();
    }
}
