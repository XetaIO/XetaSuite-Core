<?php

declare(strict_types=1);

namespace XetaSuite\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use XetaSuite\Http\Requests\V1\Suppliers\StoreSupplierRequest;
use XetaSuite\Http\Requests\V1\Suppliers\UpdateSupplierRequest;
use XetaSuite\Http\Resources\V1\Items\ItemResource;
use XetaSuite\Http\Resources\V1\Suppliers\SupplierDetailResource;
use XetaSuite\Models\Supplier;

class SupplierController extends Controller
{
    /**
     * Display a listing of suppliers.
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Supplier::class);

        $suppliers = Supplier::query()
            ->with('creator')
            ->when(request('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'ILIKE', "%{$search}%")
                        ->orWhere('description', 'ILIKE', "%{$search}%");
                });
            })
            ->when(request('sort_by'), function ($query, $sortBy) {
                $direction = request('sort_direction', 'asc');
                $allowedSorts = ['name', 'item_count', 'created_at'];
                if (in_array($sortBy, $allowedSorts)) {
                    $query->orderBy($sortBy, $direction === 'desc' ? 'desc' : 'asc');
                }
            }, function ($query) {
                $query->orderBy('name');
            })
            ->paginate(20);

        return SupplierDetailResource::collection($suppliers);
    }

    /**
     * Store a newly created supplier.
     */
    public function store(StoreSupplierRequest $request): SupplierDetailResource
    {
        $supplier = Supplier::create([
            'created_by_id' => $request->user()->id,
            'name' => $request->validated('name'),
            'description' => $request->validated('description'),
        ]);

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

        $items = $supplier->items()
            ->with('site')
            ->when(request('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'ILIKE', "%{$search}%")
                        ->orWhere('description', 'ILIKE', "%{$search}%")
                        ->orWhere('reference', 'ILIKE', "%{$search}%");
                });
            })
            ->when(request('sort_by'), function ($query, $sortBy) {
                $direction = request('sort_direction', 'asc');
                $allowedSorts = ['name', 'description', 'reference', 'current_stock', 'purchase_price', 'created_at'];
                if (in_array($sortBy, $allowedSorts)) {
                    $query->orderBy($sortBy, $direction === 'desc' ? 'desc' : 'asc');
                }
            }, function ($query) {
                $query->orderBy('name');
            })
            ->paginate(20);

        return ItemResource::collection($items);
    }

    /**
     * Update the specified supplier.
     */
    public function update(UpdateSupplierRequest $request, Supplier $supplier): SupplierDetailResource
    {
        $supplier->update($request->validated());

        return new SupplierDetailResource($supplier);
    }

    /**
     * Remove the specified supplier.
     */
    public function destroy(Supplier $supplier): JsonResponse
    {
        $this->authorize('delete', $supplier);

        // Check if supplier has items before deleting
        if ($supplier->items()->exists()) {
            return response()->json([
                'message' => __('suppliers.cannot_delete_has_items'),
            ], 422);
        }

        $supplier->delete();

        return response()->json([
            'message' => __('suppliers.deleted'),
        ]);
    }
}
