## 1. Backend

- [x] 1.1 Add `shared_fields` storage to contact requests.
- [x] 1.2 Validate allowed shared field values.
- [x] 1.3 Filter contact request payloads by direction, status, and shared fields.
- [x] 1.4 Update notifications to avoid identity leakage.

## 2. Frontend

- [x] 2.1 Add shared-data selector to contact request form.
- [x] 2.2 Show shared requester data in received requests.
- [x] 2.3 Show accepted shared data in sent and received requests.

## 3. Verification

- [x] 3.1 Test unshared fields are absent from API JSON.
- [x] 3.2 Test identity-reserved notifications are neutral.
- [x] 3.3 Test accepted and rejected flows reveal correct fields.
