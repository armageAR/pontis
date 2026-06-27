<?php
namespace App\Http\Controllers;
use App\Models\ContactRequest;
use App\Models\PontisNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactRequestController extends Controller {
    public function index(Request $request): JsonResponse {
        $user = $request->user();
        $direction = $request->get('direction', 'received'); // sent or received
        $query = $direction === 'sent'
            ? ContactRequest::where('requester_id', $user->id)->with(['requestee:id,name,last_name'])
            : ContactRequest::where('requestee_id', $user->id)->with(['requester:id,name,last_name']);
        if ($request->filled('status')) $query->where('status', $request->status);
        return response()->json($query->latest()->paginate(20));
    }

    public function store(Request $request): JsonResponse {
        $user = $request->user();
        $data = $request->validate([
            'requestee_id' => 'required|integer|exists:users,id|different:requester_id',
            'service_id'   => 'nullable|integer|exists:services,id',
            'need_id'      => 'nullable|integer|exists:needs,id',
            'message'      => 'required|string|max:1000',
        ]);
        abort_if($data['requestee_id'] == $user->id, 422, 'No podés solicitarte contacto a vos mismo.');
        $data['requester_id'] = $user->id;
        $data['expires_at'] = now()->addDays(30);
        $cr = ContactRequest::create($data);
        $requestee = \App\Models\User::find($data['requestee_id']);
        PontisNotification::create(['user_id'=>$data['requestee_id'],'type'=>'contact_received','title'=>'Nueva solicitud de contacto','body'=>"{$user->name} quiere ponerse en contacto con vos.",'data'=>['contact_request_id'=>$cr->id,'requester_name'=>$user->name]]);
        return response()->json($cr->load(['requester:id,name,last_name','requestee:id,name,last_name']), 201);
    }

    public function accept(Request $request, ContactRequest $contactRequest): JsonResponse {
        abort_if($contactRequest->requestee_id !== $request->user()->id, 403);
        abort_if($contactRequest->status !== 'pending', 422, 'Esta solicitud ya fue resuelta.');
        $data = $request->validate(['response_message' => 'nullable|string|max:1000']);
        $contactRequest->update(['status'=>'accepted','response_message'=>$data['response_message'] ?? null]);
        PontisNotification::create(['user_id'=>$contactRequest->requester_id,'type'=>'contact_accepted','title'=>'Solicitud de contacto aceptada','body'=>"{$contactRequest->requestee->name} aceptó tu solicitud de contacto.",'data'=>['contact_request_id'=>$contactRequest->id]]);
        return response()->json($contactRequest->fresh()->load(['requester:id,name,last_name,email,phone,whatsapp','requestee:id,name,last_name,email,phone,whatsapp']));
    }

    public function reject(Request $request, ContactRequest $contactRequest): JsonResponse {
        abort_if($contactRequest->requestee_id !== $request->user()->id, 403);
        abort_if($contactRequest->status !== 'pending', 422, 'Esta solicitud ya fue resuelta.');
        $data = $request->validate(['response_message' => 'nullable|string|max:500']);
        $contactRequest->update(['status'=>'rejected','response_message'=>$data['response_message'] ?? null]);
        PontisNotification::create(['user_id'=>$contactRequest->requester_id,'type'=>'contact_rejected','title'=>'Solicitud de contacto rechazada','body'=>"{$contactRequest->requestee->name} rechazó tu solicitud de contacto.",'data'=>['contact_request_id'=>$contactRequest->id]]);
        return response()->json($contactRequest->fresh());
    }

    public function cancel(Request $request, ContactRequest $contactRequest): JsonResponse {
        abort_if($contactRequest->requester_id !== $request->user()->id, 403);
        abort_if(!in_array($contactRequest->status, ['pending']), 422, 'No se puede cancelar en este estado.');
        $contactRequest->update(['status'=>'cancelled']);
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
        return response()->json($contactRequest->fresh());
    }
}
