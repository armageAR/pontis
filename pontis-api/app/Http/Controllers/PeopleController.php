<?php
namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class PeopleController extends Controller {
    public function index(Request $request): JsonResponse {
        $request->validate([
            'q'              => 'nullable|string|min:2|max:100',
            'workshop_id'    => 'nullable|integer',
            'province'       => 'nullable|string|max:100',
            'locality'       => 'nullable|string|max:100',
            'country'        => 'nullable|string|max:100',
            'masonic_status' => 'nullable|string',
            'page'           => 'nullable|integer',
            'per_page'       => 'nullable|integer|min:0|max:200',
        ]);
        $authUser = auth()->user();
        $query = User::query()->with(['workshops:id,name,number']);
        if ($request->filled('q')) {
            $q = mb_strtolower($request->q);
            $query->where(function ($qb) use ($q) {
                $qb->whereRaw('unaccent(LOWER(name)) like unaccent(?)', ["%{$q}%"])
                   ->orWhereRaw('unaccent(LOWER(last_name)) like unaccent(?)', ["%{$q}%"])
                   ->orWhereRaw('unaccent(LOWER(email)) like unaccent(?)', ["%{$q}%"])
                   ->orWhereRaw("CAST(masonic_id AS TEXT) like ?", ["%{$q}%"]);
            });
        }
        if ($request->filled('workshop_id')) {
            $query->whereHas('workshops', fn($q) => $q->where('workshops.id', $request->workshop_id));
        }
        // Usuarios no-superadmin solo ven hermanos de sus propios talleres
        if ($authUser->role !== 'superadmin') {
            $myIds = $authUser->workshops()->pluck('workshops.id');
            $query->whereHas('workshops', fn($q) => $q->whereIn('workshops.id', $myIds));
        }
        if ($request->filled('province')) {
            $query->where('province', $request->province);
        }
        if ($request->filled('locality')) {
            $loc = mb_strtolower($request->locality);
            $query->whereRaw('unaccent(LOWER(locality)) like unaccent(?)', ["%{$loc}%"]);
        }
        if ($request->filled('country')) {
            $query->where('country', $request->country);
        }
        if ($request->filled('masonic_status')) {
            $query->where('masonic_status', $request->masonic_status);
        }
        $perPage = (int) $request->input('per_page', 10);
        if ($perPage <= 0) $perPage = $query->count() ?: 1;

        $people = $query->whereNotNull('email_verified_at')
            ->select(['id','name','last_name','masonic_id','masonic_status','province','locality','country','profession','role','status'])
            ->orderBy('last_name')->orderBy('name')
            ->paginate($perPage);
        return response()->json($people);
    }
}
