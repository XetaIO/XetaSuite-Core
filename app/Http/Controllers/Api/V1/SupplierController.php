<?php

declare(strict_types=1);

namespace XetaSuite\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use XetaSuite\Actions\Suppliers\CreateSupplier;
use XetaSuite\Actions\Suppliers\DeleteSupplier;
use XetaSuite\Actions\Suppliers\UpdateSupplier;
use XetaSuite\Http\Requests\V1\Suppliers\StoreSupplierRequest;
use XetaSuite\Http\Requests\V1\Suppliers\UpdateSupplierRequest;
use XetaSuite\Http\Resources\V1\Items\ItemResource;
use XetaSuite\Http\Resources\V1\Suppliers\SupplierDetailResource;
use XetaSuite\Models\Supplier;
use XetaSuite\Services\SupplierService;

class SupplierController extends Controller
{
    public function __construct(
        private readonly SupplierService $supplierService
    ) {}

    /**
     * Display a listing of suppliers.
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Supplier::class);

        $suppliers = $this->supplierService->getPaginatedSuppliers([
            'search' => request('search'),
            'sort_by' => request('sort_by'),
            'sort_direction' => request('sort_direction'),
        ]);

        return SupplierDetailResource::collection($suppliers);
    }

    /**
     * Store a newly created supplier.
     */
    public function store(StoreSupplierRequest $request, CreateSupplier $action): SupplierDetailResource
    {
        $supplier = $action->handle($request->user(), $request->validated());

        return new SupplierDetailResource($supplier);
    }

    /**
     * Display the specified supplier.
     */
    public function show(Supplier $supplier): SupplierDetailResource
    {
        $this->authorize('view', $supplier);

        $supplier->load('creator');

        return new SupplierDetailResource($supplier);
    }

    /**
     * Display a paginated listing of items for the specified supplier.
     */
    public function items(Supplier $supplier): AnonymousResourceCollection
    {
        $this->authorize('view', $supplier);

        $items = $this->supplierService->getSupplierItems($supplier, [
            'search' => request('search'),
            'sort_by' => request('sort_by'),
            'sort_direction' => request('sort_direction'),
        ]);

        return ItemResource::collection($items);
    }

    /**
     * Update the specified supplier.
     */
    public function update(UpdateSupplierRequest $request, Supplier $supplier, UpdateSupplier $action): SupplierDetailResource
    {
        $supplier = $action->handle($supplier, $request->validated());

        return new SupplierDetailResource($supplier);
    }

    /**
     * Remove the specified supplier.
     */
    public function destroy(Supplier $supplier, DeleteSupplier $action): JsonResponse
    {
        $this->authorize('delete', $supplier);

        $result = $action->handle($supplier);

        if (! $result['success']) {
            return response()->json([
                'message' => $result['message'],
            ], 422);
        }

        return response()->json([
            'message' => $result['message'],
        ]);
    }
}
