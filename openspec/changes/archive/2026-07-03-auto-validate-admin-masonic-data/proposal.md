## Why

The current validation flow correctly sends self-declared masonic data to administrative review, but it also does this when the actor is already an Admin de Taller for the declared Taller. That creates an unnecessary self-validation loop for the exact administrator who is authorized to validate that Taller's declarations.

## What Changes

- Self-created degrees and positions continue to start as `declared` when the actor is a regular Hermano.
- Self-created degrees and positions start as `validated` when the actor is a Superadmin.
- Self-created degrees and positions start as `validated` when the actor is an Admin de Taller for the submitted `workshop_id`.
- The system stores the actor as `validator_id` and sets `validated_at` for these auto-validated records.
- Auto-validated self-created records do not create pending validation notifications.
- Records tied to a Taller where the actor is not an admin continue to require validation by a Superadmin or an Admin de Taller for that Taller.
- Degrees without a declared Taller continue to require Superadmin authority for auto-validation; otherwise they remain `declared`.

## Capabilities

### New Capabilities

### Modified Capabilities
- `profile-degrees-positions`: Clarify when self-created masonic degree and position records bypass pending validation because the actor is already authorized to validate the declared Taller.

## Impact

- Backend: `DegreeController::store` and `UserPositionController::store` validation status assignment and notification behavior.
- Tests: add coverage for Admin de Taller self-created degree/position records in own Taller, other Taller behavior, and Superadmin self-created records.
- API response shape remains unchanged; only `validation_status`, `validator_id`, `validated_at`, and pending notification side effects differ for authorized self-created records.
