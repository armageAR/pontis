# action-privacy-indicators Specification

## Purpose
TBD.

## Requirements

### Requirement: Contact privacy summary
The UI SHALL show shared data before sending or accepting contact.

#### Scenario: User sends contact request
- **WHEN** a Hermano reviews a contact request before sending
- **THEN** the UI MUST show which identity and contact fields will be shared

### Requirement: Expandable privacy details
The UI SHALL allow users to inspect details when a privacy summary combines multiple rules.

#### Scenario: User opens privacy details
- **WHEN** a Hermano expands a privacy indicator
- **THEN** the UI MUST show the relevant audience, shared data, and audit note
