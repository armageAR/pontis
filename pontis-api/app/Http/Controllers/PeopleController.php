<?php
namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class PeopleController extends Controller {
    public function index(Request $request): JsonResponse {
        $request->validate(['q'=>'nullable|string|min:2|max:100','workshop_id'=>'nullable|integer','province'=>'nullable|string','masonic_status'=>'nullable|string','page'=>'nullable|integer']);
        $query = User::query()->with(['workshops:id,name,number']);
        if ($request->filled('q')) {
            $q = mb_strtolower($request->q);
            $query->where(function ($qb) use ($q) {
                $qb->whereRaw('LOWER(name) like ?', ["%{$q}%"])
                   ->orWhereRaw('LOWER(last_name) like ?', ["%{$q}%"])
                   ->orWhereRaw('LOWER(email) like ?', ["%{$q}%"])
                   ->orWhereRaw("CAST(masonic_id AS TEXT) like ?", ["%{$q}%"]);
            });
        }
        if ($request->filled('workshop_id')) {
            $query->whereHas('workshops', fn($q) => $q->where('workshops.id', $request->workshop_id));
        }
        if ($request->filled('province')) {
            $query->where('province', $request->province);
        }
        if ($request->filled('masonic_status')) {
            $query->where('masonic_status', $request->masonic_status);
        }
        $people = $query->whereNotNull('email_verified_at')
            ->select(['id','name','last_name','masonic_id','masonic_status','province','locality','profession','role','status'])
            ->orderBy('last_name')->orderBy('name')
            ->paginate(20);
        return response()->json($people);
    }
}
