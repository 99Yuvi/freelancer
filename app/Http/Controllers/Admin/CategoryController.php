<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        return response()->json(['data' => Category::with('children')->topLevel()->orderBy('sort_order')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:80'],
            'slug'       => ['nullable', 'string', 'max:100', 'unique:categories,slug'],
            'parent_id'  => ['nullable', 'exists:categories,id'],
            'icon'       => ['nullable', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $data['slug'] ??= Str::slug($data['name']);

        $category = Category::create($data);
        AuditLog::record($request->user(), 'category.created', $category);

        return response()->json(['data' => $category, 'message' => 'Category created.'], 201);
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name'       => ['sometimes', 'string', 'max:80'],
            'is_active'  => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer'],
            'icon'       => ['nullable', 'string'],
        ]);

        $category->update($data);
        AuditLog::record($request->user(), 'category.updated', $category, $data);

        return response()->json(['data' => $category->fresh(), 'message' => 'Updated.']);
    }

    public function destroy(Request $request, Category $category)
    {
        if ($category->projects()->exists()) {
            return response()->json(['message' => 'Cannot delete: category has active projects.'], 409);
        }
        $category->delete();
        AuditLog::record($request->user(), 'category.deleted', null, ['id' => $category->id]);
        return response()->json(['message' => 'Category deleted.']);
    }
}
