<?php
namespace App\Http\Controllers;
use App\Http\Controllers\ContactConsentController;
use App\Models\ContactRequest;
use App\Models\Need;
use App\Models\PontisNotification;
use App\Models\Service;
use App\Support\AuditLogger;
use App\Support\VisibilityPolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactRequestController extends Controller {
    private const ALLOWED_SHARED_FIELDS = ['identity', 'email', 'phone', 'whatsapp', 'workshop', 'profession'];
    private const DEFAULT_SHARED_FIELDS = ['identity'];
    private const ALLOWED_REASONS = ['offer_publication', 'need_publication', 'profession_search', 'workshop_location_search', 'other'];

    public function index(Request $request): JsonResponse {
        $user = $request->user();
        $direction = $request->get('direction', 'received'); // sent or received
        $query = $direction === 'sent'
            ? ContactRequest::where('requester_id', $user->id)->with([
                'requestee:id,name,last_name,email,phone,whatsapp,profession',
                'requestee.workshops:id,name,number',
            ])
            : ContactRequest::where('requestee_id', $user->id)->with([
                'requester:id,name,last_name,email,phone,whatsapp,profession',
                'requester.workshops:id,name,number',
            ]);
        if ($request->filled('status')) $query->where('status', $request->status);
        $requests = $query->latest()->paginate(20);

        if ($direction === 'sent') {
            $policy = new VisibilityPolicy($user);
            $policy->primeSettings($requests->getCollection()->pluck('requestee_id'));
            $requests->getCollection()->each(fn ($cr) => $this->maskRequesteeForRequester($cr, $policy));
        } else {
            $requests->getCollection()->each(fn ($cr) => $this->filterRequesterForRequestee($cr));
        }

        return response()->json($requests);
    }

    public function store(Request $request): JsonResponse {
        $user = $request->user();
        $data = $request->validate([
            'requestee_id' => 'nullable|integer|exists:users,id|different:requester_id',
            'service_id'   => 'nullable|integer|exists:services,id',
            'need_id'      => 'nullable|integer|exists:needs,id',
            'message'      => 'required|string|min:10|max:1000',
            'reason_type'  => 'required|string|in:' . implode(',', self::ALLOWED_REASONS),
            'source_context' => 'nullable|array',
            'source'       => 'nullable|string|in:search,publications',
            'shared_fields' => 'sometimes|array',
            'shared_fields.*' => 'string|in:' . implode(',', self::ALLOWED_SHARED_FIELDS),
        ]);
        $data = $this->resolveMediatedTarget($data, $user->id);
        abort_if($data['requestee_id'] == $user->id, 422, 'No podés solicitarte contacto a vos mismo.');
        $requestee = \App\Models\User::findOrFail($data['requestee_id']);
        abort_if(($user->status?->value ?? (string) $user->status) === 'o_eterno', 422, 'La cuenta no tiene interacciones activas.');
        abort_if(($requestee->status?->value ?? (string) $requestee->status) === 'o_eterno', 422, 'Este Hermano no tiene contacto activo.');
        $source = $data['source'] ?? (($data['service_id'] ?? null) || ($data['need_id'] ?? null) ? 'publications' : 'search');
        abort_if(! ContactConsentController::sourceAllowed($requestee, $source), 422, 'Este Hermano no acepta solicitudes de contacto desde este origen.');
        unset($data['source']);
        $data['shared_fields'] = $this->normalizeSharedFields($data['shared_fields'] ?? ContactConsentController::defaultSharedFields($user));
        $data['requester_id'] = $user->id;
        $data['expires_at'] = now()->addDays(30);
        $cr = ContactRequest::create($data);
        AuditLogger::log($request, 'contact_request.created', $cr, 'created', [
            'requestee_id' => $cr->requestee_id,
            'service_id' => $cr->service_id,
            'need_id' => $cr->need_id,
            'reason_type' => $cr->reason_type,
            'shared_fields' => $cr->shared_fields,
        ]);
        $requesterName = in_array('identity', $data['shared_fields'], true)
            ? $user->name
            : VisibilityPolicy::MASKED_NAME;
        $body = "{$requesterName} quiere ponerse en contacto con vos.";
        $notificationData = ['contact_request_id' => $cr->id];
        if (in_array('identity', $data['shared_fields'], true)) {
            $notificationData['requester_name'] = $user->name;
        }
        PontisNotification::create(['user_id'=>$data['requestee_id'],'type'=>'contact_received','title'=>'Nueva solicitud de contacto','body'=>$body,'data'=>$notificationData]);

        $cr->load(['requester:id,name,last_name,email,phone,whatsapp,profession','requester.workshops:id,name,number','requestee:id,name,last_name']);
        $this->filterRequesterForRequestee($cr);
        $this->maskRequesteeForRequester($cr, new VisibilityPolicy($user));
        return response()->json($cr, 201);
    }

    public function accept(Request $request, ContactRequest $contactRequest): JsonResponse {
        abort_if($contactRequest->requestee_id !== $request->user()->id, 403);
        abort_if(!in_array($contactRequest->status, ['pending', 'info_requested'], true), 422, 'Esta solicitud ya fue resuelta.');
        $data = $request->validate(['response_message' => 'nullable|string|max:1000']);
        $contactRequest->update(['status'=>'accepted','response_message'=>$data['response_message'] ?? null]);
        AuditLogger::log($request, 'contact_request.accepted', $contactRequest, 'accepted', [
            'requester_id' => $contactRequest->requester_id,
            'requestee_id' => $contactRequest->requestee_id,
        ]);
        PontisNotification::create(['user_id'=>$contactRequest->requester_id,'type'=>'contact_accepted','title'=>'Solicitud de contacto aceptada','body'=>"{$contactRequest->requestee->name} aceptó tu solicitud de contacto.",'data'=>['contact_request_id'=>$contactRequest->id]]);
        $fresh = $contactRequest->fresh()->load([
            'requester:id,name,last_name,email,phone,whatsapp,profession',
            'requester.workshops:id,name,number',
            'requestee:id,name,last_name,email,phone,whatsapp',
        ]);
        $this->filterRequesterForRequestee($fresh);
        return response()->json($fresh);
    }

    public function reject(Request $request, ContactRequest $contactRequest): JsonResponse {
        abort_if($contactRequest->requestee_id !== $request->user()->id, 403);
        abort_if(!in_array($contactRequest->status, ['pending', 'info_requested'], true), 422, 'Esta solicitud ya fue resuelta.');
        $data = $request->validate(['response_message' => 'nullable|string|max:500']);
        $contactRequest->update(['status'=>'rejected','response_message'=>$data['response_message'] ?? null]);
        AuditLogger::log($request, 'contact_request.rejected', $contactRequest, 'rejected', [
            'requester_id' => $contactRequest->requester_id,
            'requestee_id' => $contactRequest->requestee_id,
        ]);
        // Un rechazo no revela información adicional: si el solicitante no
        // califica para ver la identidad del destinatario, se notifica enmascarado.
        $requesterPolicy = new VisibilityPolicy($contactRequest->requester);
        $requesteeName = $requesterPolicy->displayName($contactRequest->requestee);
        PontisNotification::create(['user_id'=>$contactRequest->requester_id,'type'=>'contact_rejected','title'=>'Solicitud de contacto rechazada','body'=>"{$requesteeName} rechazó tu solicitud de contacto.",'data'=>['contact_request_id'=>$contactRequest->id]]);
        return response()->json($contactRequest->fresh());
    }

    public function requestInfo(Request $request, ContactRequest $contactRequest): JsonResponse {
        abort_if($contactRequest->requestee_id !== $request->user()->id, 403);
        abort_if($contactRequest->status !== 'pending', 422, 'Solo se puede pedir más información en solicitudes pendientes.');
        $data = $request->validate(['response_message' => 'required|string|max:1000']);
        $contactRequest->update(['status'=>'info_requested','response_message'=>$data['response_message']]);
        AuditLogger::log($request, 'contact_request.info_requested', $contactRequest, 'info_requested', [
            'requester_id' => $contactRequest->requester_id,
            'requestee_id' => $contactRequest->requestee_id,
        ]);
        $requesterPolicy = new VisibilityPolicy($contactRequest->requester);
        $requesteeName = $requesterPolicy->displayName($contactRequest->requestee);
        PontisNotification::create([
            'user_id'=>$contactRequest->requester_id,
            'type'=>'contact_info_requested',
            'title'=>'Más información solicitada',
            'body'=>"{$requesteeName} pidió más información sobre tu solicitud de contacto.",
            'data'=>['contact_request_id'=>$contactRequest->id],
        ]);
        $fresh = $contactRequest->fresh()->load([
            'requester:id,name,last_name,email,phone,whatsapp,profession',
            'requester.workshops:id,name,number',
        ]);
        $this->filterRequesterForRequestee($fresh);
        return response()->json($fresh);
    }

    public function cancel(Request $request, ContactRequest $contactRequest): JsonResponse {
        abort_if($contactRequest->requester_id !== $request->user()->id, 403);
        abort_if(!in_array($contactRequest->status, ['pending']), 422, 'No se puede cancelar en este estado.');
        $contactRequest->update(['status'=>'cancelled']);
        AuditLogger::log($request, 'contact_request.cancelled', $contactRequest, 'cancelled');
        return response()->json($contactRequest->fresh());
    }

    public function close(Request $request, ContactRequest $contactRequest): JsonResponse {
        $user = $request->user();
        abort_if(
            $contactRequest->requester_id !== $user->id && $contactRequest->requestee_id !== $user->id,
            403
        );
        abort_if($contactRequest->status !== 'accepted', 422, 'Solo se pueden cerrar solicitudes aceptadas.');
        $contactRequest->update(['status' => 'closed']);
        AuditLogger::log($request, 'contact_request.closed', $contactRequest, 'closed');
        return response()->json($contactRequest->fresh());
    }

    /**
     * Enmascara la identidad del destinatario en la vista del solicitante
     * mientras no haya consentimiento (solicitud no aceptada) y el destinatario
     * no tenga su identidad visible para el solicitante.
     */
    private function maskRequesteeForRequester(ContactRequest $cr, VisibilityPolicy $policy): void
    {
        if (! $cr->requestee || in_array($cr->status, ['accepted', 'closed'], true)) {
            return; // aceptar (o haber aceptado) es el consentimiento que revela identidad
        }
        if ($policy->identityFor($cr->requestee)->visible) {
            return;
        }
        $cr->requestee->name = VisibilityPolicy::MASKED_NAME;
        $cr->requestee->last_name = null;
        $cr->requestee->setAttribute('anonymous', true);
        $cr->requestee_id = null;
    }

    private function resolveMediatedTarget(array $data, int $requesterId): array
    {
        abort_if(($data['service_id'] ?? null) && ($data['need_id'] ?? null), 422, 'Elegí una sola publicación como contexto.');

        if ($data['service_id'] ?? null) {
            $service = Service::query()
                ->where('status', 'active')
                ->where('expires_at', '>', now())
                ->findOrFail($data['service_id']);
            abort_if($service->user_id === $requesterId, 422, 'No podés solicitarte contacto a vos mismo.');
            $data['requestee_id'] = $service->user_id;
        }

        if ($data['need_id'] ?? null) {
            $need = Need::query()
                ->where('status', 'active')
                ->where('expires_at', '>', now())
                ->findOrFail($data['need_id']);
            abort_if($need->user_id === $requesterId, 422, 'No podés solicitarte contacto a vos mismo.');
            $data['requestee_id'] = $need->user_id;
        }

        abort_if(empty($data['requestee_id']), 422, 'La solicitud necesita un destinatario o una publicación válida.');
        return $data;
    }

    private function normalizeSharedFields(array $fields): array
    {
        return collect($fields)
            ->filter(fn ($field) => in_array($field, self::ALLOWED_SHARED_FIELDS, true))
            ->unique()
            ->values()
            ->all();
    }

    private function filterRequesterForRequestee(ContactRequest $cr): void
    {
        if (! $cr->requester) return;

        $fields = $this->normalizeSharedFields($cr->shared_fields ?? self::DEFAULT_SHARED_FIELDS);
        $requester = $cr->requester;

        if (! in_array('identity', $fields, true)) {
            $requester->id = null;
            $requester->name = VisibilityPolicy::MASKED_NAME;
            $requester->last_name = null;
            $requester->setAttribute('anonymous', true);
        }
        foreach (['email', 'phone', 'whatsapp', 'profession'] as $field) {
            if (! in_array($field, $fields, true)) {
                $requester->makeHidden($field);
                unset($requester->{$field});
            }
        }
        if (in_array('workshop', $fields, true)) {
            $principal = $requester->workshops?->first(fn ($w) => (bool) ($w->pivot->is_principal ?? false))
                ?? $requester->workshops?->first();
            $requester->setAttribute('principal_workshop', $principal ? [
                'id' => $principal->id,
                'name' => $principal->name,
                'number' => $principal->number,
            ] : null);
        }
        $requester->unsetRelation('workshops');
    }
}
