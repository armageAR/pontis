<?php
namespace App\Http\Controllers;
use App\Enums\UserStatus;
use App\Models\User;
use App\Models\UserVisibilitySetting;
use App\Support\ProfileVisibility;
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
            'scope'          => 'nullable|string|in:roster,search',
            'page'           => 'nullable|integer',
            'per_page'       => 'nullable|integer|min:0|max:200',
        ]);

        // "Buscar hermanos": búsqueda regida por las reglas de visibilidad de
        // cada perfil, independiente de la pertenencia de talleres del que busca.
        if ($request->input('scope') === 'search') {
            return $this->visibilitySearch($request);
        }

        // "Mis Hermanos": roster de co-miembros de los talleres del usuario.
        $authUser = auth()->user();
        $query = User::query()->with([
            'workshops' => fn($q) => $q->select('workshops.id', 'workshops.name', 'workshops.number'),
        ]);
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
        // Usuarios no-superadmin solo ven hermanos activos de sus propios talleres
        // (incluyéndose a sí mismos, ya que son miembros activos de su taller).
        if ($authUser->role !== 'superadmin') {
            $myIds = $authUser->workshops()->pluck('workshops.id');
            $query->where('status', UserStatus::ACTIVE->value)
                  ->whereHas('workshops', fn($q) => $q->whereIn('workshops.id', $myIds));
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

        // Identidad enmascarada: si el viewer no califica para la audiencia de
        // Identidad y el Hermano marcó "aparecer sin revelar identidad", figura
        // como "Hermano registrado" (nombre, apellido y matrícula ocultos).
        $ids = $people->getCollection()->pluck('id');
        $identitySettings = UserVisibilitySetting::whereIn('user_id', $ids)
            ->where('block', 'identity')
            ->get()->keyBy('user_id');
        $vis = new ProfileVisibility($authUser);
        $selfId = $authUser->id;

        // Aplanar el rol del pivote en cada taller (para identificar admins)
        // y enmascarar la identidad de los hermanos anónimos (excepto uno mismo).
        $people->getCollection()->each(function ($u) use ($identitySettings, $vis, $selfId) {
            $workshopIds = $u->workshops->pluck('id')->all();
            $principalId = optional($u->workshops->first(fn($w) => (bool) ($w->pivot->is_principal ?? false)))->id;
            $idSetting = $identitySettings->get($u->id);
            $idLevel = $idSetting->visibility ?? 'workshop';
            $anonSearch = (bool) ($idSetting->anonymous_search ?? false);
            $identityVisible = $u->id === $selfId
                || $vis->canSee($idLevel, $u->id, $workshopIds, $principalId);

            $u->setRelation('workshops', $u->workshops->map(fn($w) => [
                'id'            => $w->id,
                'name'          => $w->name,
                'number'        => $w->number,
                'workshop_role' => $w->pivot->role ?? 'member',
            ]));

            $isAnon = ! $identityVisible && $anonSearch;
            if ($isAnon) {
                $u->name       = 'Hermano registrado';
                $u->last_name  = null;
                $u->masonic_id = null;
            }
            $u->anonymous = $isAnon;
        });

        return response()->json($people);
    }

    /**
     * Búsqueda de Hermanos regida por la visibilidad de cada sección del perfil.
     * Un Hermano aparece si coincidís por un campo que podés ver y, o bien podés
     * ver su identidad (aparece con nombre), o marcó "anónimo" (aparece como
     * "Hermano registrado"). Si limitó su identidad y no calificás (y no es
     * anónimo), no aparece.
     */
    private function visibilitySearch(Request $request): JsonResponse
    {
        $viewer = auth()->user();
        $vis = new ProfileVisibility($viewer);

        $hasQ        = $request->filled('q');            // identidad
        $hasWorkshop = $request->filled('workshop_id');  // institucional (siempre buscable)
        $hasLocation = $request->filled('province') || $request->filled('locality') || $request->filled('country');
        $hasMasonic  = $request->filled('masonic_status');

        if (! $hasQ && ! $hasWorkshop && ! $hasLocation && ! $hasMasonic) {
            return response()->json(['data' => [], 'current_page' => 1, 'last_page' => 1, 'total' => 0]);
        }

        $query = User::query()
            ->where('status', UserStatus::ACTIVE->value)
            ->whereNotNull('email_verified_at')
            ->with(['workshops' => fn($q) => $q->select('workshops.id', 'workshops.name', 'workshops.number')]);

        if ($hasQ) {
            $q = mb_strtolower($request->q);
            $query->where(function ($qb) use ($q) {
                $qb->whereRaw('unaccent(LOWER(name)) like unaccent(?)', ["%{$q}%"])
                   ->orWhereRaw('unaccent(LOWER(last_name)) like unaccent(?)', ["%{$q}%"])
                   ->orWhereRaw('unaccent(LOWER(email)) like unaccent(?)', ["%{$q}%"])
                   ->orWhereRaw("CAST(masonic_id AS TEXT) like ?", ["%{$q}%"]);
            });
        }
        if ($hasWorkshop) {
            $query->whereHas('workshops', fn($q) => $q->where('workshops.id', $request->workshop_id));
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
        if ($hasMasonic) {
            $query->where('masonic_status', $request->masonic_status);
        }

        $candidates = $query->orderBy('last_name')->orderBy('name')->limit(1000)->get();

        $settingsByUser = UserVisibilitySetting::whereIn('user_id', $candidates->pluck('id'))
            ->get()->groupBy('user_id');

        $level = function ($settings, string $block): string {
            return optional($settings->firstWhere('block', $block))->visibility ?? 'workshop';
        };
        $principalOf = fn ($u) => optional($u->workshops->first(fn($w) => (bool) ($w->pivot->is_principal ?? false)))->id;

        $shaped = $candidates->map(function ($u) use ($vis, $settingsByUser, $level, $principalOf, $hasQ, $hasLocation, $hasMasonic) {
            $settings = $settingsByUser->get($u->id) ?? collect();
            $workshopIds = $u->workshops->pluck('id')->all();
            $principalId = $principalOf($u);

            // La identidad se evalúa en dos pasos: primero la audiencia
            // configurada; el flag de aparición anónima solo habilita un
            // resultado enmascarado para quienes no califican para la audiencia.
            $idSetting = $settings->firstWhere('block', 'identity');
            $idLevel = $idSetting->visibility ?? 'workshop';
            $anonSearch = (bool) ($idSetting->anonymous_search ?? false);
            $identityVisible = $vis->canSee($idLevel, $u->id, $workshopIds, $principalId);
            $isAnon = ! $identityVisible && $anonSearch;
            $locationVisible   = $vis->canSee($level($settings, 'location'), $u->id, $workshopIds, $principalId);
            $professionVisible = $vis->canSee($level($settings, 'profession'), $u->id, $workshopIds, $principalId);
            $masonicVisible    = $vis->canSee($level($settings, 'masonic'), $u->id, $workshopIds, $principalId);

            // Permisos por criterio aplicado.
            if ($hasQ && ! $identityVisible) return null;        // no se lo puede buscar por identidad
            if ($hasLocation && ! $locationVisible) return null;
            if ($hasMasonic && ! $masonicVisible) return null;

            // Inclusión: con nombre si la identidad es visible; enmascarado si es
            // anónimo; en cualquier otro caso no aparece.
            if (! $identityVisible && ! $isAnon) return null;

            return [
                'id'             => $u->id,
                'name'           => $identityVisible ? $u->name : 'Hermano registrado',
                'last_name'      => $identityVisible ? $u->last_name : null,
                'masonic_id'     => $identityVisible ? $u->masonic_id : null,
                'masonic_status' => $masonicVisible ? $u->masonic_status : null,
                'province'       => $locationVisible ? $u->province : null,
                'locality'       => $locationVisible ? $u->locality : null,
                'country'        => $locationVisible ? $u->country : null,
                'profession'     => $professionVisible ? $u->profession : null,
                'role'           => $u->role,
                'status'         => $u->status instanceof \BackedEnum ? $u->status->value : $u->status,
                'anonymous'      => ! $identityVisible,
                'workshops'      => $u->workshops->map(fn($w) => [
                    'id'            => $w->id,
                    'name'          => $w->name,
                    'number'        => $w->number,
                    'workshop_role' => $w->pivot->role ?? 'member',
                ])->values(),
            ];
        })->filter()->values();

        $page = max(1, (int) $request->input('page', 1));
        $perPage = (int) $request->input('per_page', 20);
        if ($perPage <= 0) $perPage = 20;
        $total = $shaped->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $data = $shaped->slice(($page - 1) * $perPage, $perPage)->values();

        return response()->json([
            'data'         => $data,
            'current_page' => $page,
            'last_page'    => $lastPage,
            'total'        => $total,
        ]);
    }
}
