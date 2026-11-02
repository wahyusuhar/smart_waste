<?php

namespace App\Http\Controllers\Api;

use App\Actions\CreateReport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\IndexReportRequest;
use App\Http\Requests\Api\StoreReportRequest;
use App\Http\Resources\ReportResource;
use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class ReportController extends Controller
{
    public function index(IndexReportRequest $request): AnonymousResourceCollection
    {
        $reports = $request->user()->reports()
            ->when($request->validated('status'), fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return ReportResource::collection($reports);
    }

    public function store(StoreReportRequest $request, CreateReport $createReport): JsonResponse
    {
        $report = $createReport->handle(
            $request->user(),
            $request->file('photo'),
            $request->safe()->except('photo'),
        );

        return (new ReportResource($report->load('statusLogs.changedBy')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Report $report): ReportResource
    {
        Gate::authorize('view', $report);

        return new ReportResource($report->load('statusLogs.changedBy'));
    }
}
