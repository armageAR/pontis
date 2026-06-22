<?php

namespace App\Http\Controllers\Admin;

use App\Enums\WorkshopStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignWorkshopUsersRequest;
use App\Http\Requests\Admin\RemoveWorkshopUsersRequest;
use App\Http\Requests\Admin\StoreWorkshopRequest;
use App\Http\Requests\Admin\UpdateWorkshopRequest;
use App\Http\Requests\Admin\WorkshopIndexRequest;
use App\Http\Resources\UserResource;
use App\Http\Resources\WorkshopResource;
use App\Models\Workshop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class WorkshopController extends Controller
{
    public function index(WorkshopIndexRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Workshop::class);

        $query = Workshop::query();

        if ($request->user()->isAdmin()) {
            $query->whereHas('users', fn ($q) => $q->where('user_id', $request->user()->id));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('zone_name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%");
            });
        }

        if ($request->filled('zone_number')) {
            $query->where('zone_number', $request->input('zone_number'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('work_day')) {
            $query->where('work_day', $request->input('work_day'));
        }

        if ($request->filled('city')) {
            $query->where('city', $request->input('city'));
        }

        if ($request->filled('province')) {
            $query->where('province', $request->input('province'));
        }

        $sortBy = $request->input('sort_by', 'number');
        $sortDirection = $request->input('sort_direction', 'asc');
        $query->orderBy($sortBy, $sortDirection);

        $perPage = $request->input('per_page', 15);

        return WorkshopResource::collection($query->paginate($perPage));
    }

    public function show(Workshop $workshop): WorkshopResource
    {
        Gate::authorize('view', $workshop);

        if (request()->has('include') && str_contains(request()->input('include'), 'users')) {
            $workshop->load('users');
        }

        return new WorkshopResource($workshop);
    }

    public function store(StoreWorkshopRequest $request): JsonResponse
    {
        Gate::authorize('create', Workshop::class);

        $workshop = Workshop::create($request->validated());

        return (new WorkshopResource($workshop))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateWorkshopRequest $request, Workshop $workshop): WorkshopResource
    {
        Gate::authorize('update', $workshop);

        $workshop->update($request->validated());

        return new WorkshopResource($workshop);
    }

    public function destroy(Workshop $workshop): JsonResponse
    {
        Gate::authorize('delete', $workshop);

        $workshop->delete();

        return response()->json(null, 204);
    }

    public function disable(Workshop $workshop): WorkshopResource
    {
        Gate::authorize('disable', $workshop);

        $workshop->update(['status' => WorkshopStatus::DISABLED]);

        return new WorkshopResource($workshop);
    }

    public function enable(Workshop $workshop): WorkshopResource
    {
        Gate::authorize('enable', $workshop);

        $workshop->update(['status' => WorkshopStatus::ACTIVE]);

        return new WorkshopResource($workshop);
    }

    public function users(Workshop $workshop): AnonymousResourceCollection
    {
        Gate::authorize('viewUsers', $workshop);

        $query = $workshop->users();

        if (request()->filled('search')) {
            $search = request()->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (request()->filled('role')) {
            $query->where('role', request()->input('role'));
        }

        $perPage = request()->input('per_page', 15);

        return UserResource::collection($query->paginate($perPage));
    }

    public function assignUsers(AssignWorkshopUsersRequest $request, Workshop $workshop): WorkshopResource
    {
        Gate::authorize('assignUsers', $workshop);

        $workshop->users()->syncWithoutDetaching($request->input('user_ids'));

        if ($request->has('include') && str_contains($request->input('include', ''), 'users')) {
            $workshop->load('users');
        }

        return new WorkshopResource($workshop);
    }

    public function removeUsers(RemoveWorkshopUsersRequest $request, Workshop $workshop): WorkshopResource
    {
        Gate::authorize('removeUsers', $workshop);

        $workshop->users()->detach($request->input('user_ids'));

        return new WorkshopResource($workshop);
    }
}
