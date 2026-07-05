## ADDED Requirements

### Requirement: Search modal contact reuses shared-data selection
The contact request flow opened from the search profile modal SHALL let the requester choose which personal data blocks to share using the same behavior as the full profile page.

#### Scenario: User selects shared data in search modal flow
- **WHEN** a Hermano creates a contact request from the search profile modal
- **THEN** the UI MUST let the requester select shared fields
- **AND** the created request MUST persist the selected shared fields
- **AND** unselected fields MUST NOT be shown to the recipient

#### Scenario: Shared-data privacy summary is shown
- **WHEN** a Hermano reviews the contact request from the search profile modal
- **THEN** the UI MUST show a privacy summary of the selected shared fields before submission
