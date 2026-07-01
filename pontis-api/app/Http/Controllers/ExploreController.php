<?php
namespace App\Http\Controllers;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExploreController extends Controller {
    public function services(Request $request): JsonResponse {
        $request->validate([
            'q'           => 'nullable|string|min:2|max:100',
            'category_id' => 'nullable|integer|exists:service_categories,id',
            'scope'       => 'nullable|string|in:my_workshop,my_workshops,registered,all',
            'province'    => 'nullable|string|max:100',
            'locality'    => 'nullable|string|max:100',
            'page'        => 'nullable|integer',
        ]);

        $viewer = $request->user();
        $viewerWorkshopIds = $viewer->workshops()->pluck('workshops.id');

        $query = Service::with(['user:id,name,last_name,profession,locality,province', 'category:id,name'])
            ->where('user_id', '!=', $viewer->id)
            ->where('status', 'active');

        // Resolve which visibility levels the viewer qualifies for
        // Build a list of eligible service IDs based on visibility rules
        $query->where(function ($q) use ($viewer, $viewerWorkshopIds) {
            // Always include services visible to all registered users
            $q->whereIn('visibility', ['registered', 'anonymous']);

            // Include workshop-level services if viewer shares any workshop with the service owner
            if ($viewerWorkshopIds->isNotEmpty()) {
                $ownerIdsInPrincipalWorkshops = User::whereHas('workshops', function ($wq) use ($viewerWorkshopIds) {
                    $wq->whereIn('workshops.id', $viewerWorkshopIds)
                        ->where('user_workshop.is_principal', true);
                })->pluck('id');

                $ownerIdsInSameWorkshops = User::whereHas('workshops', function ($wq) use ($viewerWorkshopIds) {
                    $wq->whereIn('workshops.id', $viewerWorkshopIds);
                })->pluck('id');

                if ($ownerIdsInPrincipalWorkshops->isNotEmpty()) {
                    $q->orWhere(function ($sub) use ($ownerIdsInPrincipalWorkshops) {
                        $sub->where('visibility', 'workshop')
                            ->whereIn('user_id', $ownerIdsInPrincipalWorkshops);
                    });
                }

                if ($ownerIdsInSameWorkshops->isNotEmpty()) {
                    $q->orWhere(function ($sub) use ($ownerIdsInSameWorkshops) {
                        $sub->where('visibility', 'my_workshops')
                            ->whereIn('user_id', $ownerIdsInSameWorkshops);
                    });
                }
            }
        });

        // Scope filter
        $scope = $request->input('scope', 'all');
        if ($scope === 'my_workshop') {
            $query->whereIn('user_id',
                User::whereHas('workshops', fn($q) => $q->where('workshops.id', $viewerWorkshopIds->first() ?? 0))->pluck('id')
            );
        } elseif ($scope === 'my_workshops') {
            $query->whereIn('user_id',
                User::whereHas('workshops', fn($q) => $q->whereIn('workshops.id', $viewerWorkshopIds))->pluck('id')
            );
        }

        if ($request->filled('q')) {
            $q = mb_strtolower($request->q);
            $query->where(function ($qb) use ($q) {
                $qb->whereRaw('unaccent(LOWER(title)) like unaccent(?)', ["%{$q}%"])
                   ->orWhereRaw('unaccent(LOWER(description)) like unaccent(?)', ["%{$q}%"]);
            });
        }

        if ($request->filled('category_id')) {
            $query->where('service_category_id', $request->category_id);
        }

        if ($request->filled('province')) {
            $query->whereHas('user', fn($q) => $q->where('province', $request->province));
        }

        if ($request->filled('locality')) {
            $query->whereHas('user', fn($q) => $q->whereRaw('unaccent(LOWER(locality)) like unaccent(?)', ['%' . mb_strtolower($request->locality) . '%']));
        }

        $results = $query->orderByDesc('created_at')->paginate(20);

        // Apply identity masking for anonymous visibility
        $results->getCollection()->transform(function ($service) {
            if ($service->visibility === 'anonymous') {
                $service->user = [
                    'id' => null,
                    'name' => 'Hermano registrado',
                    'last_name' => null,
                    'profession' => $service->user?->profession,
                    'locality' => $service->user?->locality,
                    'province' => $service->user?->province,
                    'anonymous' => true,
                ];
            }
            return $service;
        });

        return response()->json($results);
    }

    public function needs(Request $request): JsonResponse {
        $request->validate([
            'q'           => 'nullable|string|min:2|max:100',
            'category_id' => 'nullable|integer|exists:service_categories,id',
            'scope'       => 'nullable|string|in:my_workshop,my_workshops,registered,all',
            'province'    => 'nullable|string|max:100',
            'locality'    => 'nullable|string|max:100',
            'page'        => 'nullable|integer',
        ]);

        $viewer = $request->user();
        $viewerWorkshopIds = $viewer->workshops()->pluck('workshops.id');

        $query = \App\Models\Need::with(['user:id,name,last_name,locality,province', 'category:id,name'])
            ->where('user_id', '!=', $viewer->id)
            ->whereIn('status', ['open', 'searching', 'with_matches']);

        $query->where(function ($q) use ($viewer, $viewerWorkshopIds) {
            $q->whereIn('visibility', ['registered', 'anonymous']);

            if ($viewerWorkshopIds->isNotEmpty()) {
                $ownerIdsInPrincipalWorkshops = User::whereHas('workshops', function ($wq) use ($viewerWorkshopIds) {
                    $wq->whereIn('workshops.id', $viewerWorkshopIds)
                        ->where('user_workshop.is_principal', true);
                })->pluck('id');

                $ownerIdsInSameWorkshops = User::whereHas('workshops', function ($wq) use ($viewerWorkshopIds) {
                    $wq->whereIn('workshops.id', $viewerWorkshopIds);
                })->pluck('id');

                if ($ownerIdsInPrincipalWorkshops->isNotEmpty()) {
                    $q->orWhere(function ($sub) use ($ownerIdsInPrincipalWorkshops) {
                        $sub->where('visibility', 'workshop')
                            ->whereIn('user_id', $ownerIdsInPrincipalWorkshops);
                    });
                }

                if ($ownerIdsInSameWorkshops->isNotEmpty()) {
                    $q->orWhere(function ($sub) use ($ownerIdsInSameWorkshops) {
                        $sub->where('visibility', 'my_workshops')
                            ->whereIn('user_id', $ownerIdsInSameWorkshops);
                    });
                }
            }
        });

        if ($request->filled('q')) {
            $q = mb_strtolower($request->q);
            $query->where(function ($qb) use ($q) {
                $qb->whereRaw('unaccent(LOWER(title)) like unaccent(?)', ["%{$q}%"])
                   ->orWhereRaw('unaccent(LOWER(description)) like unaccent(?)', ["%{$q}%"]);
            });
        }

        if ($request->filled('category_id')) {
            $query->where('service_category_id', $request->category_id);
        }

        if ($request->filled('province')) {
            $query->whereHas('user', fn($q) => $q->where('province', $request->province));
        }

        if ($request->filled('locality')) {
            $query->whereHas('user', fn($q) => $q->whereRaw('unaccent(LOWER(locality)) like unaccent(?)', ['%' . mb_strtolower($request->locality) . '%']));
        }

        $results = $query->orderByDesc('created_at')->paginate(20);

        $results->getCollection()->transform(function ($need) {
            if ($need->visibility === 'anonymous') {
                $need->user = [
                    'id' => null,
                    'name' => 'Hermano registrado',
                    'last_name' => null,
                    'locality' => $need->user?->locality,
                    'province' => $need->user?->province,
                    'anonymous' => true,
                ];
            }
            return $need;
        });

        return response()->json($results);
    }
}
