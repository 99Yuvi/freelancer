<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Skill;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::active()
            ->topLevel()
            ->with(['children' => fn($q) => $q->active()])
            ->orderBy('sort_order')
            ->get();

        return response()->json(['data' => $categories]);
    }

    public function skills()
    {
        $skills = Skill::approved()->with('category:id,name')->orderBy('name')->get();
        return response()->json(['data' => $skills]);
    }
}
