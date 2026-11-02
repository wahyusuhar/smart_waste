<?php

namespace App\Http\Controllers\Api;

use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\IndexReportRequest;
use App\Http\Requests\Api\StoreReportRequest;
use App\Http\Resources\ReportResource;
use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Throwable;

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

    public function store(StoreReportRequest $request): JsonResponse
    {
        $user = $request->user();
        $photoPath = $request->file('photo')->store('reports', 'public');

        try {
            $report = DB::transaction(function () use ($request, $user, $photoPath) {
                $report = $user->reports()->create([
                    ...$request->safe()->except('photo'),
                    'photo_path' => $photoPath,
                ]);

                // The first history entry records who created the report (the reporter).
                $report->statusLogs()->create([
                    'changed_by' => $user->id,
                    'from_status' => null,
                    'to_status' => ReportStatus::Pending,
                ]);

                return $report;
            });
        } catch (Throwable $e) {
            // Without a database row the stored photo would be an orphan file.
            Storage::disk('public')->delete($photoPath);

            throw $e;
        }

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
