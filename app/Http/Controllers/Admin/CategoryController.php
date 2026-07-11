<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('posts.update'), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
            'slug' => ['nullable', 'alpha_dash', 'max:255', 'unique:categories,slug'],
        ]);
        Category::create($data);

        return back()->with('success', 'Category created.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        abort_unless($request->user()->can('posts.update'), 403);
        $category->update($request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('categories')->ignore($category)],
            'slug' => ['nullable', 'alpha_dash', 'max:255', Rule::unique('categories')->ignore($category)],
        ]));

        return back()->with('success', 'Category updated.');
    }

    public function destroy(Request $request, Category $category): RedirectResponse
    {
        abort_unless($request->user()->can('posts.update'), 403);
        $category->delete();

        return back()->with('success', 'Category deleted.');
    }
}
