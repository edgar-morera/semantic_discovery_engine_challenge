# Skill: Git Commit Conventions

## Formato base — Conventional Commits

```
<type>: <description>

[body opcional]

[footer obligatorio]
```

## Types permitidos

| Type | Cuándo usarlo |
|---|---|
| `feat` | Nueva funcionalidad |
| `fix` | Corrección de bug |
| `test` | Añadir o corregir tests |
| `refactor` | Cambio de código sin cambio de comportamiento |
| `chore` | Tareas de mantenimiento (deps, config, docker) |
| `docs` | Documentación |
| `arch` | Decisiones de arquitectura (nueva capa, patrón) |

## Trazabilidad IA (obligatoria)

Cada commit debe indicar en el footer el nivel de participación de la IA.
Cuando hay trabajo mixto, desglosar explícitamente qué hizo cada parte:

| Footer | Significado |
|---|---|
| `AI-collab` | La IA generó el grueso; el dev revisó, validó y tomó decisiones de diseño. Desglosar si hay contribuciones diferenciadas (ver formato extendido). |
| `AI-pair` | Desarrollo conjunto; criterio compartido en cada decisión. |
| `AI-rejected: <motivo>` | La IA propuso algo concreto que fue descartado. |
| `AI-ignored` | Desarrollo 100% propio sin asistencia. |

### Formato extendido (cuando hay contribuciones diferenciadas)

Usar cuando la IA y el dev aportaron partes distinguibles:

```
AI-collab
AI: <qué generó la IA>
Dev: <qué decidió/diseñó/corrigió el dev>
```

## Ejemplos

```
feat: add ProductId value object

AI-ignored
```

```
chore: add PHP-FPM + Nginx + MySQL + Qdrant stack

AI-collab
AI: generated docker-compose base and Nginx/PHP-FPM config
Dev: chose versions, added Qdrant, adjusted ports and volumes
```

```
feat: add Money value object with EUR precision

AI-collab
AI: generated VO with validation and arithmetic operations
Dev: rejected float for amount, enforced int (cents) as type
AI-rejected: AI suggested float for amount, used int (cents) instead
```

```
test: add unit tests for IndexProductCommandHandler

AI-pair
AI: structured test cases and mocks
Dev: defined the business scenarios to cover
```

## Reglas adicionales

- Descripción en inglés, imperativo, sin punto final.
- Máximo 72 caracteres en la primera línea.
- El body explica el **por qué**, no el **qué**.
- Un commit = una unidad lógica de cambio. No mezclar dominio con infraestructura. Comprobación previa y notificación en caso no cumplirse esta regla.
