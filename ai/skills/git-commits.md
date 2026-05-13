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

Cada commit debe indicar en el footer el nivel de participación de la IA:

| Footer | Significado |
|---|---|
| `AI-collab` | La IA generó el grueso, revisado y validado por el dev |
| `AI-pair` | Desarrollo conjunto, criterio compartido |
| `AI-rejected: <motivo>` | La IA propuso algo concreto que fue descartado |
| `AI-ignored` | Desarrollo 100% propio sin asistencia |

## Ejemplos

```
feat(domain): add ProductId value object

AI-ignored
```

```
chore(docker): add PHP-FPM + Nginx + MySQL + Qdrant stack

AI-collab
```

```
feat(domain): add Money value object with EUR precision

AI-rejected: AI suggested float for amount, used int (cents) instead
```

```
test(application): add unit tests for IndexProductCommandHandler

AI-pair
```

## Reglas adicionales

- Descripción en inglés, imperativo, sin punto final.
- Máximo 72 caracteres en la primera línea.
- El body explica el **por qué**, no el **qué**.
- Un commit = una unidad lógica de cambio. No mezclar dominio con infraestructura. Comprobación previa y notificación en caso no cumplirse esta regla.
