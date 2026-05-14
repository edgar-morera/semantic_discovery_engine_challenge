# PLAN.md — Semantic Discovery Engine

Este documento describe el plan de trabajo para resolver el desafío del Semantic Discovery Engine, estructurado en 8 fases incrementales que entregan valor funcional completo en cada iteración.

Cada fase aplica Arquitectura Hexagonal estricta y el principio LEAN (iteración rápida, entrega incremental). Los commits son atómicos y encapsulados, cada uno completa una funcionalidad específica sin afectar otras capas de la arquitectura.

Adicionalmente, **cada decisión y commit incluye trazabilidad explícita del nivel de colaboración con IA**: **AI-pair** (dev lidera decisiones, IA genera bajo demanda), **AI-collab** (IA + dev en paridad), **AI-rejected** (IA propone pero se descarta por criterio), o **Dev** (decisión puramente del desarrollador).

## Fases

### Fase 1 — Docker + Infraestructura

Configurar stack Docker (PHP-FPM, Nginx, MySQL 8, Qdrant, Redis) con persistencia - **AI-collab**

- IA: generó stack completo con bind-mount persistence, docker-compose, Dockerfiles
- Dev: definió arquitectura de servicios, revisó y validó configuración

### Fase 2 — Symfony Skeleton

Instalar Symfony 7 con arquitectura hexagonal estricta, XML mapping en Doctrine - **AI-rejected + AI-pair**

- IA: propuso usar atributos Doctrine en entidades de dominio
- Dev: rechazó para mantener pureza hexagonal; iteró configuración de dual buses Messenger con IA

### Fase 3 — Feature: Crear producto

#### Subfase 3a — Dominio: modelar agregado Product con VOs - **AI-pair**

- IA: generó ficheros bajo demanda, revisó, aplicó ajustes dirigidos por dev
- Dev: diseñó modelo de dominio, lideró todas las decisiones

#### Subfase 3b — Aplicación: CreateProductCommand + CreateProductCommandHandler - **AI-collab**

- IA: generó command class, handler y unit tests
- Dev: definió contrato del caso de uso e interacciones con puertos

#### Subfase 3c — Infraestructura: Doctrine ORM, repository, controller, migración - **AI-rejected + AI-collab**

- Dev: corrigió a Product.orm.xml (convención Doctrine); rechazó test funcional con DB real en favor de unitarios con mocks
- IA: regeneró mapping correcto, DTOs, migración, controller, tests

### Fase 4 — Feature: Indexar producto

#### Subfase 4a — Dominio: VO Embedding + puerto EmbeddingService - **AI-collab**

- IA: generó Embedding VO con validación y lógica de constructor
- Dev: definió constante DIMENSIONS y reglas de validación

#### Subfase 4b — Aplicación: IndexProductCommand + IndexProductCommandHandler - **AI-collab**

- IA: generó todas las clases y tests unitarios (3 casos)
- Dev: definió contrato del caso de uso (fuego y olvido tras indexación)

#### Subfase 4c — Infraestructura: adaptador HuggingFace, cliente Qdrant - **AI-collab**

- IA: generó HuggingFaceEmbeddingService, QdrantProductSearchRepository, controller, tests
- Dev: identificó discrepancia de formato de respuesta HuggingFace (flat vs. nested), definió estrategia de env vars en docker-compose

### Fase 5 — Feature: Buscar productos

#### Subfase 5a — Dominio: VO SearchResult + ProductSearchPort.search() - **AI-collab**

- IA: implementó SearchResult VO, stub de ProductSearchPort.search()
- Dev: definió cambio de contrato (incluir score en resultados)

#### Subfase 5b — Aplicación: SearchProductsQuery + SearchProductsQueryHandler - **AI-collab**

- IA: generó todas las clases, tests (4 casos), estrategia skip-not-found
- Dev: definió forma de DTO (incluir score); revisó enfoque de tests (fix willReturnMap → willReturnCallback para comparación de VOs)

#### Subfase 5c — Infraestructura: QdrantProductSearchRepository + SearchProductsController - **AI-collab**

- IA: generó QdrantProductSearchRepository, controller, tests
- Dev: definió restricciones de límite (máx 50, default 10)

### Fase 6 — CLI Seed

Crear comando Symfony para cargar 357 productos reales de API Siroko con descripción semántica - **AI-collab**

- IA: generó estructura de comando, procesamiento de fichero PHP, script de extracción de datos
- Dev: definió alcance del comando, formato de descripción semántica en prosa española, revisó exactitud de datos

### Fase 7 — Performance & Calidad

#### Subfase 7a — Análisis estático - **AI-collab**

Instalar y configurar PHPStan, PHP CS Fixer, Deptrac, PHPMD + targets Makefile

- IA: generó configuraciones y targets Makefile (cs, cs-fix, stan, deptrac, phpmd, analyse)
- Dev: definió exclusiones de ruleset para convenciones DDD y constraints de interfaces Doctrine

#### Subfase 7b — Medición de rendimiento con k6 - **AI-collab**

Instalar k6, crear scripts de carga por endpoint, medir baseline, analizar resultados

- IA: generó setup k6, scripts de carga, configuración de servicio Docker
- Dev: definió targets de rendimiento, analizó resultados de baseline

**Baseline (10 VUs, 60s, `GET /products/search`)**:

| Métrica | Valor |
|---|---|
| p50 | 351 ms |
| p95 | 5 220 ms |
| avg | 1 199 ms |
| max | 10 034 ms |

#### Subfase 7c — Mejoras de rendimiento

**Mejora 1 — Caché Redis para embeddings (TTL 5 min)** - **AI-collab**

- IA: generó patrón decorator, configuración de caché, wiring de servicios
- Dev: definió estrategia de caché, valor TTL, diagnosticó env var faltante

**Mejora 2 — Indexación asincrónica vía Messenger** - **AI-collab**

- IA: generó patrón async, configuración de transport
- Dev: definió estrategia async, gestión del ciclo de vida del worker

**Mejora 3 — Ajuste pool PHP-FPM** - **AI-collab**

- IA: generó configuración del pool
- Dev: validó bajo carga de test

**Resultados finales**:

| Métrica | Antes | Después | Mejora |
|---|---|---|---|
| p50 | 351 ms | 13.4 ms | −96.2% |
| p95 | 5 220 ms | 15.4 ms | −99.7% |
| avg | 1 199 ms | 13.4 ms | −98.9% |
| max | 10 034 ms | 22 ms | −99.8% |

### Fase 8 — Documentación

**Crear ficheros README.md, PLAN.md, DECISIONS.md y key-prompts.md** - **AI-pair**

- IA: completó y propuso cambios para enriquecer las descripciones y normalizar el estilo
- Dev: redactó los ficheros base y supervisó a la IA 
