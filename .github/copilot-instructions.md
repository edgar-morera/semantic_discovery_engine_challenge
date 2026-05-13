# Convenciones del Proyecto — Semantic Discovery Engine

## Stack técnico
- PHP 8.3 + Symfony 7
- MySQL 8
- Qdrant (Vector DB para almacenar y buscar embeddings)
- Docker Compose

## Arquitectura
- Arquitectura Hexagonal aplicando DDD estricto.

## Estructura de carpetas
```
src/
└── <Product>/
    ├── Domain/
    │   ├── Model/
    │   ├── ValueObject/
    │   ├── Repository/
    │   └── Event/
    ├── Application/
    │   └── <UseCaseName>/
    │       ├── <UseCaseName>Command.php       # o Query.php
    │       ├── <UseCaseName>CommandHandler.php # o QueryHandler.php
    │       └── <UseCaseName>Response.php      # DTO de salida (solo Queries)
    └── Infrastructure/
        ├── Persistence/
        ├── Http/             # controllers Symfony
        └── External/         # clientes API externos
```

## Código
- `declare(strict_types=1)` en todos los ficheros PHP.
- Hacer uso estricto de principios SOLID, especialmente SRP y DIP.
- Excepciones de dominio propias, nunca excepciones genéricas de PHP.
- Endpoints deben estar documentados con OpenAPI.

## Testing
- PHPUnit. Un test por caso de uso mínimo.
- Tests unitarios en `tests/Unit/`, de integración en `tests/Integration/`.
- Nomenclatura: `<ClaseTesteada>Test.php`, métodos en `snake_case` descriptivo.

## Skills disponibles
| Skill | Cuándo usarla |
|---|---|
| [git-commits.md](../ai/skills/git-commits.md) | Usado para la normalización de commits |
