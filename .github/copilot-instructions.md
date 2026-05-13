# Convenciones del Proyecto — Semantic Discovery Engine

## Descripción del proyecto
Desarrollo de un motor de búsqueda semántica para productos, aplicando Arquitectura Hexagonal, DDD y CQRS, con trazabilidad de la colaboración con IA.

## Requerimientos del proyecto
- Un endpoint para **crear productos** con campos básicos (nombre y descripción semántica).
- Un endpoint para **indexar productos** (generación de embeddings simple mediante API externa).
- Un **endpoint de búsqueda** que devuelva productos ordenados por relevancia semántica.

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
    │   ├── Port/             # outbound port interfaces (e.g. EmbeddingService)
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
