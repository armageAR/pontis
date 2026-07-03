## 1. Backend

- [x] 1.1 Inventory endpoints returning user, publication, contact, or notification data.
- [x] 1.2 Add resources/serializers for community payloads. (CommunityPersonResource + payloads de VisibilityPolicy.)
- [x] 1.3 Add separate resources/serializers for admin payloads. (AdminUserResource; /users y taller-include gated a admins.)
- [x] 1.4 Filter notification data according to minimum-data rules. (Inventario verificado: bodies referencian campo, no valores sensibles; contacto ya enmascarado por política; cubierto con tests.)

## 2. Frontend

- [x] 2.1 Update components that rely on fields no longer always returned. (Verificado: tipos ya nullable en api/people; UsersPage maneja el 403 con su estado de error; ningún componente comunitario consume email/phone/dni.)
- [x] 2.2 Add fallback rendering for withheld fields. (Ya existente: renderizado condicional y `—` en PeoplePage/MisHermanosPage para masonic_id/masonic_status.)

## 3. Verification

- [x] 3.1 Add JSON privacy assertions for search, profile, explore, contact, and notifications. (MinimumDataPayloadsTest, 10 tests.)
- [x] 3.2 Test admin endpoints still provide needed admin fields. (/users y /admin/workshops/{id}/users con email para admins.)
- [x] 3.3 Run frontend build/typecheck after payload changes. (tsc -b + vite build OK.)
