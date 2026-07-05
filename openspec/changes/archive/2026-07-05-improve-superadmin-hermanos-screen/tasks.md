## 1. Backend/API

- [x] 1.1 Reforzar el endpoint administrativo de Hermanos para que `GET /users` utilizado por esta pantalla responda 403 a actores que no sean Superadmin.
- [x] 1.2 Exponer `last_name` y `province` en el payload administrativo de usuario si no están disponibles para la tabla/filtros.
- [x] 1.3 Agregar validación y aplicación del filtro `province` sobre la provincia del Hermano.
- [x] 1.4 Mantener el filtro `workshop_id` por membresía de Taller y asegurar que combine correctamente con búsqueda, rol de Taller y estado.
- [x] 1.5 Ampliar la whitelist de `sort_by` para `last_name`, `name`, `email`, `workshops`, `status` y `actions`.
- [x] 1.6 Implementar ordenamiento estable para `workshops` y `actions`, incluyendo desempate determinístico.

## 2. Frontend Access And Filters

- [x] 2.1 Proteger la pantalla de Hermanos dentro de Administración para que solo el Superadmin pueda verla o disparar su carga de datos.
- [x] 2.2 Reemplazar el filtro de Taller por el `WorkshopPicker` existente o una variante compatible que permita escribir y seleccionar por nombre o número.
- [x] 2.3 Agregar filtro de Provincia y conectarlo al parámetro `province`, reiniciando a página 1 al cambiar.
- [x] 2.4 Asegurar que limpiar filtros remueva los parámetros vacíos sin romper paginación ni ordenamiento.

## 3. Hermanos Table

- [x] 3.1 Cambiar las columnas visibles al orden Apellido/s, Nombre/s, Email, Talleres, Estado, Acciones.
- [x] 3.2 Separar el render de apellido/s y nombre/s usando `last_name` y `name`.
- [x] 3.3 Hacer clickeables y ordenables todos los encabezados visibles, incluida la columna Acciones.
- [x] 3.4 Mantener el indicador visual de dirección de ordenamiento en el encabezado activo.
- [x] 3.5 Corregir estilos de filas/celdas para que el divisor horizontal sea continuo y no quede desalineado por la celda de nombre.

## 4. Verification

- [x] 4.1 Agregar o actualizar tests backend para autorización Superadmin-only, filtro por Provincia, filtro por Taller y ordenamiento soportado.
- [x] 4.2 Agregar o actualizar tests frontend para guardas de acceso, `WorkshopPicker` como filtro, filtro de Provincia, ordenamiento por encabezados y orden de columnas.
- [x] 4.3 Ejecutar la suite relevante de backend y frontend.
- [x] 4.4 Verificar visualmente la tabla en desktop y mobile para confirmar separadores continuos y ausencia de solapamientos.
