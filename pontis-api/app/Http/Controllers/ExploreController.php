<?php
namespace App\Http\Controllers;
use App\Models\Need;
use App\Models\Service;
use App\Support\VisibilityPolicy;
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
        $policy = new VisibilityPolicy($viewer);

        $query = Service::with(['user:id,name,last_name,profession,locality,province', 'category:id,name'])
            ->where('user_id', '!=', $viewer->id)
            ->where('status', 'active')
            ->where('expires_at', '>', now());
        $query->whereHas('user', fn($q) => $q->where('status', 'active'));

        // El alcance por visibilidad de publicación lo resuelve la política central.
        $policy->scopePublicationVisibility($query);

        // Scope filter
        $scope = $request->input('scope', 'all');
        $viewerWorkshopIds = collect($policy->viewerWorkshopIds);
        if ($scope === 'my_workshop') {
            $query->whereIn('user_id',
                \App\Models\User::whereHas('workshops', fn($q) => $q->where('workshops.id', $viewerWorkshopIds->first() ?? 0))->pluck('id')
            );
        } elseif ($scope === 'my_workshops') {
            $query->whereIn('user_id',
                \App\Models\User::whereHas('workshops', fn($q) => $q->whereIn('workshops.id', $viewerWorkshopIds))->pluck('id')
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

        // Enmascarado de identidad para visibilidad anónima (política central).
        $results->getCollection()->transform(fn ($service) => $policy->maskAnonymousAuthor($service, withProfession: true));

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
        $policy = new VisibilityPolicy($viewer);

        $query = Need::with(['user:id,name,last_name,locality,province', 'category:id,name'])
            ->where('user_id', '!=', $viewer->id)
            ->where('status', 'active')
            ->where('expires_at', '>', now());
        $query->whereHas('user', fn($q) => $q->where('status', 'active'));

        $policy->scopePublicationVisibility($query);

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

        $results->getCollection()->transform(fn ($need) => $policy->maskAnonymousAuthor($need));

        return response()->json($results);
    }
}
