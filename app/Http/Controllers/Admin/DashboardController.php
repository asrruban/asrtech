<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Group;
use App\Models\Page;
use App\Models\Product;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'clients' => User::query()->count(),
                'admins' => Admin::query()->count(),
                'products' => Product::query()->count(),
                'categories' => Category::query()->count(),
                'groups' => Group::query()->count(),
                'pages' => Page::query()->count(),
            ],
            'recentClients' => User::query()
                ->latest()
                ->limit(5)
                ->get(['id', 'name', 'email', 'created_at']),
        ]);
    }
}
