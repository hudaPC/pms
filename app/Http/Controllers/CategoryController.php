<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Category;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories.
     */
    public function index()
    {
        $categories = Category::latest()->paginate(10);

        return view('categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new category.
     */
    public function create()
    {
        return view('categories.create');
    }

    /**
     * Store a newly created category.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:30',
                'unique:categories,code',
            ],
            'name' => [
                'required',
                'string',
                'max:100',
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'status' => [
                'nullable',
                'boolean',
            ],
        ]);

        $validated['status'] = $request->has('status');

        Category::create($validated);

        return redirect()
            ->route('categories.index')
            ->with('success', 'Category created successfully.');
    }

    /**
     * Show the form for editing a category.
     */
    public function edit(Category $category)
    {
        return view('categories.edit', compact('category'));
    }

    /**
     * Update the specified category.
     */
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('categories', 'code')
                    ->ignore($category->id),
            ],
            'name' => [
                'required',
                'string',
                'max:100',
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'status' => [
                'nullable',
                'boolean',
            ],
        ]);

        $validated['status'] = $request->has('status');

        $category->update($validated);

        return redirect()
            ->route('categories.index')
            ->with('success', 'Category updated successfully.');
    }

    /**
     * Remove the specified category.
     */
    public function destroy(Category $category)
    {
        try {
            $category->delete();

            return redirect()
                ->route('categories.index')
                ->with('success', 'Category deleted successfully.');

        } catch (\Illuminate\Database\QueryException $e) {

            return redirect()
                ->route('categories.index')
                ->with('error', 'This category cannot be deleted because it is being used by one or more items.');
        }
    }
}
