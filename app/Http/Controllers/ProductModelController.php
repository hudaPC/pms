<?php

namespace App\Http\Controllers;

use App\Models\ProductModel;
use Illuminate\Http\Request;

class ProductModelController extends Controller
{
    /**
     * Display product models.
     */
    public function index()
    {
        $productModels = ProductModel::latest()
            ->paginate(10);

        return view(
            'product-models.index',
            compact('productModels')
        );
    }


    /**
     * Show create form.
     */
    public function create()
    {
        return view('product-models.create');
    }


    /**
     * Store product model.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'model_code' => [
                'required',
                'string',
                'max:100',
                'unique:product_models,model_code',
            ],

            'document_no' => [
                'nullable',
                'string',
                'max:100',
            ],

            'approval_date' => [
                'nullable',
                'date',
            ],

            'prepared_by' => [
                'nullable',
                'string',
                'max:100',
            ],

            'approved_by' => [
                'nullable',
                'string',
                'max:100',
            ],

            'revision' => [
                'nullable',
                'string',
                'max:50',
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

        $validated['status'] = $request->boolean('status');

        ProductModel::create($validated);

        return redirect()
            ->route('product-models.index')
            ->with(
                'success',
                'Product model created successfully.'
            );
    }


    /**
     * Show edit form.
     */
    public function edit(ProductModel $productModel)
    {
        return view(
            'product-models.edit',
            compact('productModel')
        );
    }


    /**
     * Update product model.
     */
    public function update(
        Request $request,
        ProductModel $productModel
    ) {
        $validated = $request->validate([
            'model_code' => [
                'required',
                'string',
                'max:100',
                'unique:product_models,model_code,' . $productModel->id,
            ],

            'document_no' => [
                'nullable',
                'string',
                'max:100',
            ],

            'approval_date' => [
                'nullable',
                'date',
            ],

            'prepared_by' => [
                'nullable',
                'string',
                'max:100',
            ],

            'approved_by' => [
                'nullable',
                'string',
                'max:100',
            ],

            'revision' => [
                'nullable',
                'string',
                'max:50',
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

        $validated['status'] = $request->boolean('status');

        $productModel->update($validated);

        return redirect()
            ->route('product-models.index')
            ->with(
                'success',
                'Product model updated successfully.'
            );
    }


    /**
     * Delete product model.
     */
    public function destroy(ProductModel $productModel)
    {
        $productModel->delete();

        return redirect()
            ->route('product-models.index')
            ->with(
                'success',
                'Product model deleted successfully.'
            );
    }
}
