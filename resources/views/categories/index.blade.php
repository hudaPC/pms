@extends('layouts.admin')

@section('title', 'Categories')

@section('content')

    <div class="container">

        {{-- Page Header --}}
        <div class="row mb-3">

            <div class="col-md-6">

                <h1 class="m-0">
                    Categories
                </h1>

                <small class="text-muted">
                    Manage item categories
                </small>

            </div>


            <div class="col-md-6 text-md-right mt-2 mt-md-0">

                <a href="{{ route('categories.create') }}" class="btn btn-primary">

                    <i class="fas fa-plus mr-1"></i>

                    Add Category

                </a>

            </div>

        </div>





        {{-- Error Message --}}
        @if(session('error'))

            <div class="alert alert-danger alert-dismissible fade show" role="alert">

                <i class="fas fa-exclamation-circle mr-1"></i>

                {{ session('error') }}

                <button type="button" class="close" data-dismiss="alert" aria-label="Close">

                    <span aria-hidden="true">
                        &times;
                    </span>

                </button>

            </div>

        @endif


        {{-- Categories Card --}}
        <div class="card card-primary card-outline">

            <div class="card-header">

                <h3 class="card-title">

                    <i class="fas fa-tags mr-2"></i>

                    Category List

                </h3>


                <div class="card-tools">

                    <span class="badge badge-primary">

                        {{ $categories->total() }}

                        {{ $categories->total() == 1 ? 'Category' : 'Categories' }}

                    </span>

                </div>

            </div>


            <div class="card-body p-0">

                <div class="table-responsive">

                    <table class="table table-bordered table-hover mb-0 table-striped table-sm">

                        <thead>

                            <tr>

                                <th style="width: 70px;">
                                    #
                                </th>

                                <th style="width: 140px;">
                                    Code
                                </th>

                                <th>
                                    Category Name
                                </th>

                                <th>
                                    Description
                                </th>

                                <th style="width: 110px;">
                                    Status
                                </th>

                                <th style="width: 150px;">
                                    Actions
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            @forelse($categories as $category)

                                <tr>

                                    {{-- Serial --}}
                                    <td>

                                        {{ $categories->firstItem() + $loop->index }}

                                    </td>


                                    {{-- Code --}}
                                    <td>

                                        <strong>
                                            {{ $category->code }}
                                        </strong>

                                    </td>


                                    {{-- Name --}}
                                    <td>

                                        {{ $category->name }}

                                    </td>


                                    {{-- Description --}}
                                    <td>

                                        {{ $category->description ?: '-' }}

                                    </td>


                                    {{-- Status --}}
                                    <td>

                                        @if($category->status)

                                            <span class="badge badge-success">

                                                <i class="fas fa-check mr-1"></i>

                                                Active

                                            </span>

                                        @else

                                            <span class="badge badge-secondary">

                                                <i class="fas fa-times mr-1"></i>

                                                Inactive

                                            </span>

                                        @endif

                                    </td>


                                    {{-- Actions --}}
                                    <td>

                                        <a href="{{ route('categories.edit', $category) }}" class="btn btn-sm btn-warning"
                                            title="Edit Category">

                                            <i class="fas fa-edit"></i>

                                        </a>


                                        <form action="{{ route('categories.destroy', $category) }}" method="POST"
                                            class="d-inline"
                                            onsubmit="return confirm('Are you sure you want to delete this category?');">

                                            @csrf

                                            @method('DELETE')

                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete Category">

                                                <i class="fas fa-trash"></i>

                                            </button>

                                        </form>

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="6" class="text-center text-muted py-4">

                                        <i class="fas fa-folder-open fa-2x mb-2"></i>

                                        <br>

                                        No categories found.

                                        <br>

                                        <a href="{{ route('categories.create') }}" class="btn btn-sm btn-primary mt-2">

                                            <i class="fas fa-plus mr-1"></i>

                                            Add First Category

                                        </a>

                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>


            {{-- Pagination --}}
            @if($categories->hasPages())

                <div class="card-footer clearfix">

                    <div class="float-right">

                        {{ $categories->links('pagination::bootstrap-4') }}

                    </div>

                </div>

            @endif

        </div>

    </div>

@endsection