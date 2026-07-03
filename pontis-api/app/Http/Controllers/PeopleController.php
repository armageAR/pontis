<?php
namespace App\Http\Controllers;
use App\Enums\UserStatus;
use App\Models\User;
use App\Support\VisibilityPolicy;
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

        // Identidad enmascarada: la decisión (audiencia + flag de aparición
        // anónima) la resuelve la política central de visibilidad.
        $policy = new VisibilityPolicy($authUser);
        $policy->primeSettings($people->getCollection()->pluck('id'));

        // Aplanar el rol del pivote en cada taller (para identificar admins)
        // y enmascarar la identidad de los hermanos anónimos (excepto uno mismo).
        // Mínimo dato: la matrícula queda ligada a la visibilidad de identidad
        // y el estado masónico al bloque "masonic".
        $people->getCollection()->transform(function ($u) use ($policy) {
            // La identidad y los bloques se resuelven antes de aplanar la
            // relación de talleres, porque la política necesita el pivote.
            $identity = $policy->identityFor($u);
            $masonicVisible = $policy->canSeeBlock($u, 'masonic');

            $u->setRelation('workshops', $u->workshops->map(fn($w) => [
                'id'            => $w->id,
                'name'          => $w->name,
                'number'        => $w->number,
                'workshop_role' => $w->pivot->role ?? 'member',
            ]));

            if ($identity->anonymous) {
                $u->name      = VisibilityPolicy::MASKED_NAME;
                $u->last_name = null;
            }
            if (! $identity->visible) {
                $u->masonic_id = null;
            }
            if (! $masonicVisible) {
                $u->masonic_status = null;
            }
            $u->anonymous = $identity->anonymous;

            // Whitelist de campos comunitarios (nunca email, teléfono ni DNI).
            return (new \App\Http\Resources\CommunityPersonResource($u))->resolve();
        });

        return response()->json($people);
    }

    /**
     * Búsqueda de Hermanos regida por la visibilidad de cada sección del perfil.
     * Un Hermano aparece si coincidís por un campo que podés ver y, o bien podés
     * ver su identidad (aparece con nombre), o marcó "anónimo" (aparece como
     * "Hermano registrado"). Si limitó su identidad y no calificás (y no es
     * anónimo), no aparece. Las decisiones las toma la política central.
     */
    private function visibilitySearch(Request $request): JsonResponse
    {
        $viewer = auth()->user();
        $policy = new VisibilityPolicy($viewer);

        $hasQ        = $request->filled('q');            // identidad visible o profesion/oficio visible
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
                   ->orWhereRaw('unaccent(LOWER(profession)) like unaccent(?)', ["%{$q}%"])
                   ->orWhereRaw('unaccent(LOWER(occupation)) like unaccent(?)', ["%{$q}%"]);
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
        $policy->primeSettings($candidates->pluck('id'));

        $q = $hasQ ? mb_strtolower($request->q) : null;
        $matches = fn ($value) => $q !== null
            && $value !== null
            && str_contains(mb_strtolower((string) $value), $q);

        $shaped = $candidates->map(function ($u) use ($policy, $hasQ, $hasLocation, $hasMasonic, $matches) {
            $identity = $policy->identityFor($u);
            $identityVisible = $identity->visible;

            $locationVisible   = $policy->canSeeBlock($u, 'location');
            $professionVisible = $policy->canSeeBlock($u, 'profession');
            $masonicVisible    = $policy->canSeeBlock($u, 'masonic');

            // Permisos por criterio aplicado.
            if ($hasQ) {
                $identityMatch = $matches($u->name) || $matches($u->last_name);
                $professionMatch = $matches($u->profession) || $matches($u->occupation);
                $matchedVisibleCriterion =
                    ($identityMatch && $identityVisible)
                    || ($professionMatch && $professionVisible);

                if (! $matchedVisibleCriterion) return null;
            }
            if ($hasLocation && ! $locationVisible) return null;
            if ($hasMasonic && ! $masonicVisible) return null;

            // Inclusión: con nombre si la identidad es visible; enmascarado si es
            // anónimo; en cualquier otro caso no aparece.
            if (! $identityVisible && ! $identity->anonymous) return null;

            return [
                'id'             => $u->id,
                'name'           => $identityVisible ? $u->name : VisibilityPolicy::MASKED_NAME,
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
