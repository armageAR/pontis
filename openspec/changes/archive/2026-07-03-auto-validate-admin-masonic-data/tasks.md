## 1. Backend

- [x] 1.1 Add a shared status decision path for self-created degree records: validated when the actor can validate the submitted Taller, otherwise declared.
- [x] 1.2 Add the same status decision path for self-created position records.
- [x] 1.3 Set `validator_id` and `validated_at` when a self-created degree or position is auto-validated.
- [x] 1.4 Suppress pending validation notifications for auto-validated self-created records.

## 2. Verification

- [x] 2.1 Add tests for Admin de Taller self-created degree and position records in an administered Taller.
- [x] 2.2 Add tests proving Admin de Taller self-created records for non-administered Talleres still require validation.
- [x] 2.3 Add tests for Superadmin self-created records, including degree records without a Taller.
- [x] 2.4 Run the affected backend feature tests for degree and position validation.

## 3. Review

- [x] 3.1 Verify profile UI badges already render the returned `validated` state correctly for auto-validated records.
- [x] 3.2 Verify pending validation pages do not list auto-validated records.
