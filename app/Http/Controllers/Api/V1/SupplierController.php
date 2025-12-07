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
    ) {
    }

    /**
     * Display a listing of suppliers.
     * Only shows suppliers the user has access to.
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
     *
     * @param  StoreSupplierRequest  $request  The incoming request.
     * @param  CreateSupplier  $action  The action to create a supplier.
     */
    public function store(StoreSupplierRequest $request, CreateSupplier $action): SupplierDetailResource
    {
        $supplier = $action->handle($request->user(), $request->validated());

        return new SupplierDetailResource($supplier);
    }

    /**
     * Display the specified supplier.
     *
     * @param  Supplier  $supplier  The supplier to display.
     */
    public function show(Supplier $supplier): SupplierDetailResource
    {
        $this->authorize('view', $supplier);

        $supplier->load('creator');

        return new SupplierDetailResource($supplier);
    }

    /**
     * Get items for a supplier (with pagination).
     *
     * @param  Supplier  $supplier  The supplier to get items for.
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
     *
     * @param  UpdateSupplierRequest  $request  The incoming request.
     * @param  Supplier  $supplier  The supplier to update.
     * @param  UpdateSupplier  $action  The action to update the supplier.
     */
    public function update(UpdateSupplierRequest $request, Supplier $supplier, UpdateSupplier $action): SupplierDetailResource
    {
        $supplier = $action->handle($supplier, $request->validated());

        return new SupplierDetailResource($supplier);
    }

    /**
     * Delete the specified supplier.
     *
     * @param  Supplier  $supplier  The supplier to delete.
     * @param  DeleteSupplier  $action  The action to delete the supplier.
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
