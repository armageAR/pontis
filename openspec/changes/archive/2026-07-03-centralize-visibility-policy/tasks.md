## 1. Backend

- [x] 1.1 Design central visibility policy service interface.
- [x] 1.2 Move profile block visibility decisions into the policy.
- [x] 1.3 Move anonymous result decisions into the policy.
- [x] 1.4 Add helpers for filtered payloads or visibility previews.
- [x] 1.5 Migrate People, PublicProfile, Explore, ContactRequest, and notifications to use the policy.

## 2. Verification

- [x] 2.1 Add unit tests for policy visibility matrix.
- [x] 2.2 Add integration tests for endpoint parity before and after migration. (Existing suite passed unchanged pre/post migration; new endpoint tests added.)
- [x] 2.3 Test anonymous result behavior through policy-backed endpoints. (Caught and fixed a real anonymity leak in explore.)
