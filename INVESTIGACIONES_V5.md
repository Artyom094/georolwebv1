# Sistema de Investigaciones V5

## Descripción
Sistema flexible de investigaciones tecnológicas para países en Georol. Permite a los GMs gestionar tecnologías organizadas en categorías y subcategorías, con costo en IT (Industria Tecnológica).

## Estructura de Base de Datos

### Tablas Creadas (V5)
1. **categorias_investigacion** - Categorías principales (Aéreo, Terrestre, Marítimo, Unidades Especiales)
2. **subcategorias_investigacion** - Subdivisiones opcionales (ej: Infantería, Blindados dentro de Terrestre)
3. **investigaciones** - Tecnologías individuales con nombre, costo IT y descripción
4. **paises_investigaciones** - Relación de investigaciones completadas por cada país

### Flexibilidad del Sistema
El diseño permite:
- ✅ Agregar/eliminar categorías dinámicamente
- ✅ Agregar/eliminar subcategorías (opcionales)
- ✅ Agregar/eliminar investigaciones individuales
- ✅ Reorganizar el orden de visualización
- ✅ Activar/desactivar investigaciones sin eliminarlas
- ✅ Adaptar el sistema a cambios futuros sin modificar código

## Archivos Creados

### Base de Datos y Migración
- [SQL/SQL V5.sql](SQL/SQL V5.sql) - Schema completo del sistema
- [migrate_v5.php](migrate_v5.php) - Script de migración automática

### Páginas Principales
- [gm_research.php](gm_research.php) - Panel de gestión para GMs
  - Crear/editar/eliminar categorías
  - Crear/editar/eliminar subcategorías
  - Crear/editar/eliminar investigaciones
  - Vista organizada por categorías
  
- [research_toggle.php](research_toggle.php) - Backend para asignar investigaciones a países

### Actualizaciones
- [view_country.php](view_country.php) - Ahora muestra investigaciones completadas del país
- [includes/header.php](includes/header.php) - Enlace a gestión de investigaciones en navbar

## Uso del Sistema

### Para Game Masters

#### 1. Gestionar Estructura (gm_research.php)
- **Nueva Categoría**: Clic en "Nueva Categoría"
  - Ejemplo: "Electrónica Avanzada"
  - Orden: para controlar posición en la lista
  
- **Nueva Subcategoría**: Clic en "Nueva Subcategoría"
  - Seleccionar categoría padre
  - Ejemplo: "Radares" dentro de "Electrónica"
  
- **Eliminar**: Botones de eliminar en cada elemento
  - ⚠️ Eliminar categoría borra todas sus investigaciones

#### 2. Agregar Investigaciones
- Clic en "Nueva Investigación"
- Completar formulario:
  - **Categoría**: Obligatorio (Aéreo, Terrestre, etc.)
  - **Subcategoría**: Opcional (si aplica)
  - **Nombre**: Ej. "Misiles Aire-Aire de 5ta Generación"
  - **Costo IT**: Cantidad de puntos IT necesarios
  - **Descripción**: Opcional, para más contexto
  - **Orden**: Para ordenar dentro de la categoría

#### 3. Asignar a Países
Desde [view_country.php](view_country.php):
- Clic en "Gestionar" en sección Investigaciones
- Marcar/desmarcar checkboxes de investigaciones completadas
- Guardar cambios
- El país verá las investigaciones en su perfil

### Para Participantes
- Ver investigaciones completadas en "Mi País"
- Ver costo total de IT invertido
- Organizadas por categorías para fácil lectura
- ❌ No pueden modificar (solo GMs)

## Estructura Inicial

### Categorías (4)
1. **Aéreo** - Sin subsecciones
2. **Terrestre** - 4 subsecciones de ejemplo:
   - Infantería
   - Blindados
   - Artillería
   - Defensa Antiaérea
3. **Marítimo** - 5 subsecciones de ejemplo:
   - Submarinos
   - Fragatas y Corbetas
   - Destructores y Cruceros
   - Portaaviones
   - Defensa Costera
4. **Unidades Especiales** - Sin subsecciones

⚠️ **Nota**: Las subsecciones son EJEMPLOS. Los GMs pueden modificarlas, eliminarlas o agregar nuevas según las necesidades del juego.

## Ejemplos de Uso

### Caso 1: Agregar Investigación Aérea
```
Categoría: Aéreo
Subcategoría: (ninguna)
Nombre: Cazas de 5ta Generación
Costo IT: 150
Descripción: Tecnología stealth y aviónica avanzada
```

### Caso 2: Investigación Terrestre con Subcategoría
```
Categoría: Terrestre
Subcategoría: Blindados
Nombre: Tanque Principal de Combate Moderno
Costo IT: 200
Descripción: Protección reactiva y sistemas de control de fuego
```

### Caso 3: Unidad Especial
```
Categoría: Unidades Especiales
Subcategoría: (ninguna)
Nombre: Fuerzas de Operaciones Especiales
Costo IT: 100
Descripción: Entrenamiento élite y equipo especializado
```

## Adaptabilidad del Sistema

### Si cambia la estructura del juego:
1. **Agregar nueva categoría**: Botón "Nueva Categoría" en gm_research.php
2. **Cambiar subsecciones**: Eliminar/agregar desde sidebar
3. **Modificar investigaciones**: Editar desde tabla principal
4. **Desactivar temporalmente**: Editar y marcar como inactiva (en base de datos)

### Sin necesidad de modificar código:
- ✅ Cambio de número de subsecciones
- ✅ Cambio de nombres de categorías
- ✅ Cambio de costos IT
- ✅ Reorganización del orden
- ✅ Agregar más de 6 unidades especiales
- ✅ Cambiar estructura de 4 categorías a N categorías

## Ventajas del Diseño

1. **Normalización 3NF**: Sin redundancia de datos
2. **Cascada en eliminación**: Borrar categoría elimina todo su contenido
3. **Relación muchos-a-muchos**: Un país puede tener múltiples investigaciones
4. **Timestamps**: Registro de cuándo se completó cada investigación
5. **Orden configurable**: Control total sobre visualización
6. **Extensible**: Fácil agregar campos futuros (requisitos, efectos, etc.)

## Consultas Útiles

### Ver todas las investigaciones de un país
```sql
SELECT i.nombre_investigacion, c.nombre_categoria, pi.fecha_completada
FROM paises_investigaciones pi
JOIN investigaciones i ON pi.id_investigacion = i.id_investigacion
JOIN categorias_investigacion c ON i.id_categoria = c.id_categoria
WHERE pi.id_pais = ?
ORDER BY c.orden;
```

### Total IT gastado por país
```sql
SELECT p.nombre_pais, SUM(i.costo_it) as total_it
FROM paises_investigaciones pi
JOIN paises p ON pi.id_pais = p.id_pais
JOIN investigaciones i ON pi.id_investigacion = i.id_investigacion
GROUP BY p.id_pais;
```

### Investigaciones más comunes
```sql
SELECT i.nombre_investigacion, COUNT(*) as veces_investigada
FROM paises_investigaciones pi
JOIN investigaciones i ON pi.id_investigacion = i.id_investigacion
GROUP BY i.id_investigacion
ORDER BY veces_investigada DESC;
```

## Próximos Pasos Sugeridos

1. Agregar investigaciones específicas para cada categoría
2. Considerar agregar campo "requisitos" (investigaciones previas necesarias)
3. Agregar campo "efectos" (bonificaciones que otorga)
4. Implementar vista de "árbol tecnológico" visual
5. Agregar notificaciones cuando un país completa investigación
6. Sistema de "investigación en progreso" con fechas estimadas

## Troubleshooting

### No aparecen investigaciones en país
- Verificar que hay investigaciones creadas en gm_research.php
- Verificar que están marcadas como "activo = 1"
- Revisar que el país las tiene asignadas en research_toggle.php

### Error al eliminar categoría
- Verificar que quieres eliminar TODAS las investigaciones asociadas
- Usar "ON DELETE CASCADE" configurado en foreign keys

### Cambios no se guardan
- Verificar rol de usuario (debe ser GM o Admin)
- Revisar permisos de sesión PHP
- Comprobar que no hay errores SQL en logs

## Testing

Para verificar la instalación:
```bash
php migrate_v5.php
```

Debe mostrar:
```
✓ Tablas de investigación creadas exitosamente
✓ Categorías iniciales insertadas
✓ Subcategorías de ejemplo creadas
¡Migración V5 completada!
```

Luego acceder a:
- http://localhost/georol-cartillas/gm_research.php (como GM)
- Agregar investigaciones de prueba
- Ver en http://localhost/georol-cartillas/view_country.php?id=X
