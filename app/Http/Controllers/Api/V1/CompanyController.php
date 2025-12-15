<?php

declare(strict_types=1);

namespace XetaSuite\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use XetaSuite\Actions\Companies\CreateCompany;
use XetaSuite\Actions\Companies\DeleteCompany;
use XetaSuite\Actions\Companies\UpdateCompany;
use XetaSuite\Http\Requests\V1\Companies\StoreCompanyRequest;
use XetaSuite\Http\Requests\V1\Companies\UpdateCompanyRequest;
use XetaSuite\Http\Resources\V1\Companies\CompanyDetailResource;
use XetaSuite\Http\Resources\V1\Maintenances\MaintenanceResource;
use XetaSuite\Models\Company;
use XetaSuite\Services\CompanyService;

class CompanyController extends Controller
{
    public function __construct(
        private readonly CompanyService $companyService
    ) {
    }

    /**
     * Display a listing of companies.
     * Only accessible from headquarters site.
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Company::class);

        $companies = $this->companyService->getPaginatedCompanies([
            'search' => request('search'),
            'sort_by' => request('sort_by'),
            'sort_direction' => request('sort_direction'),
        ]);

        return CompanyDetailResource::collection($companies);
    }

    /**
     * Store a newly created company.
     *
     * @param  StoreCompanyRequest  $request  The incoming request.
     * @param  CreateCompany  $action  The action to create a company.
     */
    public function store(StoreCompanyRequest $request, CreateCompany $action): CompanyDetailResource
    {
        $company = $action->handle($request->user(), $request->validated());

        return new CompanyDetailResource($company);
    }

    /**
     * Display the specified company.
     *
     * @param  Company  $company  The company to display.
     */
    public function show(Company $company): CompanyDetailResource
    {
        $this->authorize('view', $company);

        $company->load('creator');
        $company->loadCount('maintenances');

        return new CompanyDetailResource($company);
    }

    /**
     * Update the specified company.
     *
     * @param  UpdateCompanyRequest  $request  The incoming request.
     * @param  Company  $company  The company to update.
     * @param  UpdateCompany  $action  The action to update the company.
     */
    public function update(UpdateCompanyRequest $request, Company $company, UpdateCompany $action): CompanyDetailResource
    {
        $company = $action->handle($company, $request->validated());

        return new CompanyDetailResource($company);
    }

    /**
     * Delete the specified company.
     *
     * @param  Company  $company  The company to delete.
     * @param  DeleteCompany  $action  The action to delete the company.
     */
    public function destroy(Company $company, DeleteCompany $action): JsonResponse
    {
        $this->authorize('delete', $company);

        $result = $action->handle($company);

        if (! $result['success']) {
            return response()->json([
                'message' => $result['message'],
            ], 422);
        }

        return response()->json([
            'message' => $result['message'],
        ]);
    }

    /**
     * Get paginated maintenances for the company.
     *
     * @param  Company  $company  The company.
     */
    public function maintenances(Company $company): AnonymousResourceCollection
    {
        $this->authorize('view', $company);

        $maintenances = $this->companyService->getPaginatedMaintenances($company, [
            'search' => request('search'),
            'sort_by' => request('sort_by'),
            'sort_direction' => request('sort_direction'),
        ]);

        return MaintenanceResource::collection($maintenances);
    }

    /**
     * Get statistics for the company.
     *
     * @param  Company  $company  The company.
     */
    public function stats(Company $company): JsonResponse
    {
        $this->authorize('view', $company);

        $stats = $this->companyService->getCompanyStats($company);

        return response()->json(['data' => $stats]);
    }
}
