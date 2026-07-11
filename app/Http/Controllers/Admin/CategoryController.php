<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('posts.view'), 403);

        return response()->json(Category::withCount('posts')->orderBy('name')->get());
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('posts.update'), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
            'slug' => ['nullable', 'alpha_dash', 'max:255', 'unique:categories,slug'],
        ]);

        return response()->json(Category::create($data), 201);
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        abort_unless($request->user()->can('posts.update'), 403);
        $category->update($request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('categories')->ignore($category)],
            'slug' => ['nullable', 'alpha_dash', 'max:255', Rule::unique('categories')->ignore($category)],
        ]));

        return response()->json($category);
    }

    public function destroy(Request $request, Category $category): JsonResponse
    {
        abort_unless($request->user()->can('posts.update'), 403);
        $category->delete();

        return response()->json(status: 204);
    }
}
