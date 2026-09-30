# Specs — especificación orientada a comportamiento

Este directorio contiene las especificaciones de los módulos del sistema, escritas con el método **Spec-Driven Development (SDD)**: primero se decide *qué* debe hacer el sistema, después se diseña *cómo*, y solo entonces se escribe código.

## Convención

Una spec vive en `specs/<modulo>/` y tiene 4 archivos, en este orden de lectura:

| Archivo | Responde a | Naturaleza |
|---|---|---|
| `spec.md` | **¿Qué** debe hacer el sistema, y **por qué** | Decisiones. Cambiarlas exige revisar el archivo completo |
| `hallazgos.md` | **¿Cómo está hoy** | Evidencia con `archivo:línea`. **Inmutable**: si una spec evoluciona, se *agrega* una sección de re-verificación, no se edita |
| `plan.md` | **¿Cómo** se construye | Diseño técnico: migraciones, servicios, archivos a modificar |
| `tasks.md` | **¿En qué orden** | Checklist de implementación, con criterios de salida verificables |

## Índice

| Módulo | Estado | Alcance |
|---|---|---|
| [`inventarios/`](inventarios/spec.md) | Especificación cerrada, **pendiente de implementación** | Alta de artículos, carga inicial, ingresos, recepción de órdenes de compra, entregas, bajas y consulta de existencias |

## Flujo de trabajo

```
   spec.md          ¿qué y por qué?        ← se escribe primero, se revisa con el usuario
      │
      ├─► hallazgos.md    ¿cómo está hoy?   ← investigación con evidencia
      │
      ├─► plan.md         ¿cómo se hace?    ← diseño técnico
      │
      └─► tasks.md        ¿en qué orden?    ← punto de entrada de la implementación
```

`spec.md` se escribe primero y **se revisa antes de continuar**. Las decisiones de un módulo no se tocan durante la implementación: si aparece un requisito nuevo, se revisa la spec completa y se propaga el cambio hacia abajo.

## Reglas

1. **El español es el idioma.** Los cuatro archivos, sus nombres de sección y la prosa están en español.

2. **`spec.md` usa criterios formales.** Los requisitos funcionales se escriben en **Dado / Cuando / Entonces**, no en prosa.

3. **Los invariantes van con su número.** `INV-1` a `INV-7` se referencian desde el código, desde los mensajes de error y desde la verificación. Renumerarlos rompe las referencias.

4. **`hallazgos.md` es evidencia, no opinión.** Cada afirmación lleva `archivo:línea` o un conteo de base de datos. "Creo que el menú está comentado" no es un hallazgo; "`MenuOptions.php:123-132` está comentado desde el commit `84ab9a8`" sí.

5. **Los criterios de salida de `tasks.md` son verificables.** Un `grep` que devuelve 0 resultados es un criterio de salida. "El código se ve bien" no lo es.

6. **Un módulo nuevo copia la estructura, no el contenido.** `inventarios/` es la referencia.

7. **Estas specs se versionan en git.** Son documentación de diseño, y un cambio de decisión debe quedar en el historial. Compárese con `Notas.md`, que es un cuaderno de pendientes informal y **no** es una spec.

## Contexto del proyecto

Para las convenciones de código —estructura de CI4, PostgreSQL, Tailwind, el patrón de 4 registros del menú, la resolución de roles— ver [`../AGENTS.md`](../AGENTS.md). Las specs no repiten esas reglas: las referencian.
