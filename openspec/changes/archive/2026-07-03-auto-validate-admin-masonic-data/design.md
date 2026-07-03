## Context

Pontis already has a declared/validated lifecycle for masonic degrees and positions. Regular self-created records are saved as `declared`; admin-created records through admin endpoints are saved as `validated`; pending declarations can be validated by Superadmin or by an Admin de Taller for the record's Taller.

The gap is the profile self-management path. `DegreeController::store` and `UserPositionController::store` always save self-created records as `declared` and notify validators, even when the actor is already authorized to validate records for the submitted Taller.

## Goals / Non-Goals

**Goals:**
- Auto-validate self-created degree and position records when the actor can validate for the submitted Taller.
- Keep regular Hermano self-declarations pending validation.
- Preserve workshop-scoped authorization: an Admin de Taller only bypasses validation for Talleres where they are admin.
- Avoid pending validation notifications for records that are immediately validated.
- Add tests for self-created admin and superadmin cases.

**Non-Goals:**
- No database schema change.
- No new API endpoints or response shape changes.
- No change to degree progression, Taller membership validation, validated-record edit restrictions, or admin validation endpoints.
- No public/community exposure of validation workflow state changes.

## Decisions

- Reuse the existing `canValidateForWorkshop` authority rule for auto-validation.
  - Rationale: the bypass must match the same authorization boundary used by explicit validation.
  - Alternative considered: check only whether the actor is admin of any Taller. Rejected because a Hermano can be admin in one Taller and a regular member in another.
- Set `validation_status`, `validator_id`, and `validated_at` during the profile create action when the actor can validate that record.
  - Rationale: the record should be indistinguishable from other administratively validated records in audit and UI state.
  - Alternative considered: create as `declared` and immediately call validation logic. Rejected because it would create misleading pending notifications or duplicate audit semantics unless carefully suppressed.
- Only send pending validation notifications when the created record remains `declared`.
  - Rationale: validators should not see work that is already complete.
- Keep null-workshop degrees conservative.
  - Rationale: an Admin de Taller's authority is scoped to a declared Taller; when no Taller is declared, only Superadmin can auto-validate.

## Risks / Trade-offs

- Admin-created self records become firm immediately and existing owner restrictions can prevent later direct edits. -> Cover this in tests and rely on existing validated-record correction paths.
- Superadmin self-created records without a Taller will auto-validate while Admin de Taller records without a Taller will not. -> This follows current global vs workshop-scoped authority and should be explicit in tests.
- Pending validation counts may decrease for admins who previously saw their own declarations. -> This is the intended behavior and should require no UI changes because the API already returns `validated`.
