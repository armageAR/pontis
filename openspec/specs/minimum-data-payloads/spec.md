# minimum-data-payloads

## Purpose

Politica de minimo dato para respuestas API, notificaciones y payloads comunitarios, asegurando que datos sensibles solo se envien cuando el requester esta autorizado por visibilidad, consentimiento o contexto administrativo.

## Requirements

### Requirement: API minimum data
The API SHALL omit sensitive fields unless the requester is authorized to receive them.

#### Scenario: Community search response
- **WHEN** a Hermano receives community search results
- **THEN** the response MUST NOT include email, phone, WhatsApp, DNI, or hidden masonic id fields unless allowed by visibility and consent

### Requirement: Admin and community payload separation
The system SHALL use separate payload rules for administrative and community contexts.

#### Scenario: Superadmin opens admin user list
- **WHEN** a Superadmin uses an administrative endpoint
- **THEN** the endpoint MAY include administrative fields required for that workflow
- **AND** community endpoints MUST remain filtered

### Requirement: Notifications obey minimum data
Notifications SHALL NOT include names or sensitive details when the workflow requires identity reservation.

#### Scenario: Anonymous contact notification
- **WHEN** a contact request is sent from an identity-reserved context
- **THEN** the notification MUST NOT include the hidden user's name or contact data
