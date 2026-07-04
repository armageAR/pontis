<?php

use App\Http\Controllers\Admin\WorkshopController;
use App\Http\Controllers\Admin\WorkshopSyncController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\ChangeRequestController;
use App\Http\Controllers\ContactRequestController;
use App\Http\Controllers\ContactConsentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DegreeController;
use App\Http\Controllers\NeedController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PeopleController;
use App\Http\Controllers\PositionCatalogController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfileWorkshopController;
use App\Http\Controllers\LocalityController;
use App\Http\Controllers\ProvinceController;
use App\Http\Controllers\PublicProfileController;
use App\Http\Controllers\VisibilitySettingsController;
use App\Http\Controllers\ZoneController;
use App\Http\Controllers\ServiceCategoryController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ExploreController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserPositionController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');
Route::get('/workshops/search', [AuthController::class, 'searchWorkshops']);

Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->name('verification.verify');

Route::get('/email/change/{id}/{hash}', [AuthController::class, 'confirmEmailChange'])
    ->name('emailchange.confirm');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::post('/workshops/{workshop}/dismiss-notification', [WorkshopController::class, 'dismissNotification']);

    Route::get('/account-status',         [AuthController::class, 'accountStatus']);
    Route::post('/email/resend-verification', [AuthController::class, 'resendVerification']);

    Route::get('/users', [UserController::class, 'index']);
    Route::get('/audit-logs', [AuditLogController::class, 'index']);
    Route::get('/my-workshops', [UserController::class, 'myWorkshops']);
    Route::patch('/users/{user}', [UserController::class, 'update']);
    Route::patch('/users/{user}/status', [UserController::class, 'updateStatus']);
    Route::post('/users/{user}/o-eterno', [UserController::class, 'markOEterno']);
    Route::post('/users/{user}/o-eterno/revert', [UserController::class, 'revertOEterno']);
    Route::patch('/users/{user}/password', [UserController::class, 'updatePassword']);
    Route::post('/users/{user}/workshops/{workshop}', [UserController::class, 'addWorkshop']);
    Route::patch('/users/{user}/workshops/{workshop}', [UserController::class, 'updateWorkshopRole']);
    Route::delete('/users/{user}/workshops/{workshop}', [UserController::class, 'removeWorkshop']);

    // Perfil propio
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::patch('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/email', [ProfileController::class, 'requestEmailChange']);
    Route::get('/profile/contact-consent', [ContactConsentController::class, 'show']);
    Route::patch('/profile/contact-consent', [ContactConsentController::class, 'update']);

    // Mis Talleres (membresías activas y pendientes) desde el perfil
    Route::get('/profile/workshops', [ProfileWorkshopController::class, 'index']);
    Route::post('/profile/workshops/{workshop}/principal', [ProfileWorkshopController::class, 'setPrincipal']);

    // Grados del usuario autenticado
    Route::get('/profile/degrees', [DegreeController::class, 'index']);
    Route::post('/profile/degrees', [DegreeController::class, 'store']);
    Route::patch('/profile/degrees/{degree}', [DegreeController::class, 'update']);
    Route::delete('/profile/degrees/{degree}', [DegreeController::class, 'destroy']);
    Route::get('/admin/degree-validations', [DegreeController::class, 'pendingValidations']);
    Route::post('/admin/users/{user}/degrees', [DegreeController::class, 'storeForUser']);
    Route::post('/admin/degrees/{degree}/validate', [DegreeController::class, 'validateDeclaration']);
    Route::post('/admin/degrees/{degree}/reject', [DegreeController::class, 'rejectDeclaration']);

    // Cargos del usuario autenticado
    Route::get('/profile/positions', [UserPositionController::class, 'index']);
    Route::post('/profile/positions', [UserPositionController::class, 'store']);
    Route::patch('/profile/positions/{userPosition}', [UserPositionController::class, 'update']);
    Route::delete('/profile/positions/{userPosition}', [UserPositionController::class, 'destroy']);
    Route::get('/admin/position-validations', [UserPositionController::class, 'pendingValidations']);
    Route::post('/admin/users/{user}/positions', [UserPositionController::class, 'storeForUser']);
    Route::post('/admin/positions/{userPosition}/validate', [UserPositionController::class, 'validateDeclaration']);
    Route::post('/admin/positions/{userPosition}/reject', [UserPositionController::class, 'rejectDeclaration']);

    // Catálogo de cargos
    Route::get('/positions', [PositionCatalogController::class, 'index']);
    Route::post('/positions', [PositionCatalogController::class, 'store']);
    Route::patch('/positions/{position}', [PositionCatalogController::class, 'update']);
    Route::delete('/positions/{position}', [PositionCatalogController::class, 'destroy']);

    // Catálogo de categorías de servicios
    Route::get('/service-categories', [ServiceCategoryController::class, 'index']);
    Route::post('/service-categories', [ServiceCategoryController::class, 'store']);
    Route::patch('/service-categories/{serviceCategory}', [ServiceCategoryController::class, 'update']);
    Route::delete('/service-categories/{serviceCategory}', [ServiceCategoryController::class, 'destroy']);

    // Servicios ofrecidos
    Route::get('/services', [ServiceController::class, 'index']);
    Route::post('/services', [ServiceController::class, 'store']);
    Route::get('/services/{service}', [ServiceController::class, 'show']);
    Route::patch('/services/{service}', [ServiceController::class, 'update']);
    Route::delete('/services/{service}', [ServiceController::class, 'destroy']);
    Route::post('/services/{service}/suspend', [ServiceController::class, 'suspend']);

    // Necesidades
    Route::get('/needs', [NeedController::class, 'index']);
    Route::post('/needs', [NeedController::class, 'store']);
    Route::get('/needs/{need}', [NeedController::class, 'show']);
    Route::patch('/needs/{need}', [NeedController::class, 'update']);
    Route::delete('/needs/{need}', [NeedController::class, 'destroy']);
    Route::post('/needs/{need}/suspend', [NeedController::class, 'suspend']);

    // Provincias (catálogo)
    Route::get('/provinces', [ProvinceController::class, 'index']);

    // Localidades (catálogo, filtrables por province_id)
    Route::get('/localities', [LocalityController::class, 'index']);

    // Zonas masónicas
    Route::get('/zones', [ZoneController::class, 'index']);
    Route::post('/zones', [ZoneController::class, 'store']);
    Route::patch('/zones/{zone}', [ZoneController::class, 'update']);
    Route::delete('/zones/{zone}', [ZoneController::class, 'destroy']);

    // Configuración de visibilidad por bloque
    Route::get('/profile/visibility', [VisibilitySettingsController::class, 'index']);
    Route::post('/profile/visibility', [VisibilitySettingsController::class, 'update']);
    Route::get('/profile/publication-preview', [VisibilitySettingsController::class, 'publicationPreview']);

    // Búsqueda de personas
    Route::get('/people', [PeopleController::class, 'index']);

    // Explorar servicios y necesidades de otros hermanos (con filtro de visibilidad)
    Route::get('/explore/services', [ExploreController::class, 'services']);
    Route::get('/explore/needs', [ExploreController::class, 'needs']);

    // Ficha pública de persona
    Route::get('/people/{user}', [PublicProfileController::class, 'show']);

    // Notificaciones
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markRead']);
    Route::patch('/notifications/{notification}', [NotificationController::class, 'markOne']);

    // Solicitudes de cambio sensible
    Route::get('/change-requests', [ChangeRequestController::class, 'index']);
    Route::post('/change-requests', [ChangeRequestController::class, 'store']);
    Route::post('/change-requests/{changeRequest}/approve', [ChangeRequestController::class, 'approve']);
    Route::post('/change-requests/{changeRequest}/reject', [ChangeRequestController::class, 'reject']);
    Route::post('/change-requests/{changeRequest}/require-info', [ChangeRequestController::class, 'requireInfo']);
    Route::post('/change-requests/{changeRequest}/cancel', [ChangeRequestController::class, 'cancel']);

    // Solicitudes de contacto
    Route::get('/contact-requests', [ContactRequestController::class, 'index']);
    Route::post('/contact-requests', [ContactRequestController::class, 'store']);
    Route::post('/contact-requests/{contactRequest}/accept', [ContactRequestController::class, 'accept']);
    Route::post('/contact-requests/{contactRequest}/reject', [ContactRequestController::class, 'reject']);
    Route::post('/contact-requests/{contactRequest}/request-info', [ContactRequestController::class, 'requestInfo']);
    Route::post('/contact-requests/{contactRequest}/cancel', [ContactRequestController::class, 'cancel']);
    Route::post('/contact-requests/{contactRequest}/close', [ContactRequestController::class, 'close']);

    Route::prefix('admin')->group(function () {
        Route::get('/workshops', [WorkshopController::class, 'index']);
        Route::post('/workshops', [WorkshopController::class, 'store']);
        Route::get('/workshops/{workshop}', [WorkshopController::class, 'show']);
        Route::patch('/workshops/{workshop}', [WorkshopController::class, 'update']);
        Route::delete('/workshops/{workshop}', [WorkshopController::class, 'destroy']);

        Route::post('/workshops/{workshop}/disable', [WorkshopController::class, 'disable']);
        Route::post('/workshops/{workshop}/enable', [WorkshopController::class, 'enable']);
        Route::post('/workshops/{workshop}/join', [WorkshopController::class, 'join']);
        Route::delete('/workshops/{workshop}/leave', [WorkshopController::class, 'leave']);
        Route::get('/join-requests', [WorkshopController::class, 'pendingJoinRequests']);
        Route::post('/workshops/{workshop}/join-requests/{user}/approve', [WorkshopController::class, 'approveJoinRequest']);
        Route::post('/workshops/{workshop}/join-requests/{user}/request-correction', [WorkshopController::class, 'requestCorrection']);
        Route::post('/workshops/{workshop}/join-requests/{user}/reject', [WorkshopController::class, 'rejectJoinRequest']);

        Route::post('/workshops/gla/preview', [WorkshopSyncController::class, 'preview']);
        Route::post('/workshops/gla/apply',   [WorkshopSyncController::class, 'apply']);

        Route::get('/workshops/{workshop}/users', [WorkshopController::class, 'users']);
        Route::post('/workshops/{workshop}/users', [WorkshopController::class, 'assignUsers']);
        Route::delete('/workshops/{workshop}/users', [WorkshopController::class, 'removeUsers']);
    });
});
