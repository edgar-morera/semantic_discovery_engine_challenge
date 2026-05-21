# Semantic Discovery Engine

Solución a la Opción B **Semantic Discovery Engine (Search & AI)**. Motor de búsqueda semántica de productos: el usuario describe lo que busca en lenguaje natural y el sistema devuelve los productos más relevantes ordenados por similitud semántica, sin depender de coincidencia exacta de palabras clave.

---

## Requisitos

- [Docker](https://docs.docker.com/get-docker/) >= 24
- [Docker Compose](https://docs.docker.com/compose/) >= 2.20
- [Make](https://www.gnu.org/software/make/)
- Una API key gratuita de [HuggingFace](https://huggingface.co/settings/tokens) con acceso a Inference API

---

## Arquitectura

Arquitectura Hexagonal + DDD + CQRS estrictos. El dominio no tiene dependencias de framework. Dos buses Messenger independientes (`command.bus` con transacción Doctrine, `query.bus`). La indexación es asíncrona — el worker consume los comandos en background vía Redis Streams.

```
src/Product/
├── Domain/          # Entidades, VOs, ports, excepciones
├── Application/     # Commands, Queries y sus Handlers
└── Infrastructure/  # HTTP controllers, Doctrine, HuggingFace, Qdrant, Redis
```

---

## Stack

| Componente | Tecnología |
|---|---|
| Runtime | PHP 8.3 + Symfony 7 |
| Base de datos | MySQL 8 |
| Vector DB | Qdrant v1.9 |
| Cache / Queue | Redis 7.2 |
| Embeddings | HuggingFace — `ibm-granite/granite-embedding-97m-multilingual-r2` (384 dims) |
| Contenedores | Docker Compose |

---

## API

La especificación OpenAPI está disponible en `GET /api/doc` (UI) y `GET /api/doc.json` (JSON) cuando el entorno está levantado.

| Método | Endpoint | Descripción |
|---|---|---|
| `POST` | `/products` | Crea un producto |
| `POST` | `/products/{id}/index` | Encola la generación del embedding (202 Accepted) |
| `GET` | `/products/search?q=&limit=` | Búsqueda semántica ordenada por relevancia |

### Ejemplos

```bash
# Crear producto
curl -X POST http://localhost:8080/products \
  -H "Content-Type: application/json" \
  -d '{"name": "Chaleco térmico", "semanticDescription": "Chaleco de ciclismo para invierno."}'

# Indexar (asíncrono — devuelve 202 inmediatamente)
curl -X POST http://localhost:8080/products/{id}/index

# Buscar
curl "http://localhost:8080/products/search?q=chaleco+termico+invierno&limit=5"
```

---

## Modelado del dominio

```
Product (Aggregate Root)
├── ProductId          — UUID v4
├── ProductName        — string, no vacío, máx 255 chars
└── ProductSemanticDescription — string, no vacío

Embedding (Value Object, no persiste en MySQL)
└── values: float[384] — usado exclusivamente por EmbeddingService y ProductSearchPort

SearchResult (Value Object)
├── productId: ProductId
├── name: string
├── semanticDescription: string
└── score: float       — similitud coseno 0.0–1.0

Ports
├── ProductIdGenerator        — genera ProductId (UUID v4)
├── EmbeddingService          — genera Embedding a partir de una descripción
└── ProductSearchPort         — indexa y busca por vector en Qdrant
```

---

## Comandos disponibles

```
make help           — Lista todos los comandos disponibles

# Docker
make build          — Setup inicial: copia .env, construye imágenes, migra y hace seed
make up             — Arranca todos los servicios en background
make stop           — Para los servicios (mantiene volúmenes)
make remove         — Elimina contenedores, volúmenes e imágenes

# Base de datos
make seed-products  — Importa los 357 productos de Siroko en MySQL

# Tests
make test           — Ejecuta todos los tests PHPUnit
make test-unit      — Ejecuta solo la suite unitaria

# Calidad de código
make cs             — Revisa estilo (PHP CS Fixer, dry-run)
make cs-fix         — Corrige estilo automáticamente
make stan           — Análisis estático PHPStan nivel 8
make deptrac        — Verifica dependencias entre capas
make phpmd          — PHP Mess Detector
make analyse        — Ejecuta cs + stan + deptrac + phpmd

# Performance
make k6-smoke       — Smoke test: 1 VU, 10 iteraciones
make k6-load        — Load test: 10 VUs durante 60 s
make k6-stress      — Stress test: rampa de 1 a 50 VUs
```

## Levantar el entorno

```bash
# Setup inicial (primera vez)
make build

# O arrancar si ya está construido
make up
```

Una vez hecho el build del proyecto, hay que setear la variable de entorno `HUGGINGFACE_API_KEY=HUGGINGFACE_API_KEY` por un token válido de HuggingFace y ejecutar `make restart` para recrear los contenedores.

El worker de Messenger arranca automáticamente como servicio (`semantic_worker`) y procesa la cola de indexación en background.

Se pueden cargar productos de ejemplo. Aunque este proceso puede tardar unos minutos ya que tiene que inserta 350 productos y generar los embeddings:

```bash
make seed-products
```

---

## Tests

```bash
make test               # Todos los tests
make test-with-coverage # Todos los tests con reporte de cobertura
make analyse            # Análisis estático completo (PHPStan + CS + Deptrac + PHPMD)
```

---

## Performance

Load test con k6 (10 VUs, 60 s) sobre `GET /products/search`:

| Métrica | Baseline | Con mejoras |
|---|---|---|
| p50 | 351 ms | 13 ms |
| p95 | 5 220 ms | 15 ms |
| max | 10 034 ms | 22 ms |

Mejoras aplicadas: caché de embeddings en Redis (TTL 5 min), indexación asíncrona vía Messenger y pool PHP-FPM ampliado de 5 a 20 workers.

---

## Documentación IA

Esta prueba se ha desarrollado en colaboración con **GitHub Copilot** como pair programmer y reviewer.

La carpeta [`/ai`](./ai/) contiene el registro completo de la colaboración con IA:

- [`PLAN.md`](./ai/PLAN.md) — plan de trabajo, hitos y rol de la IA en cada fase
- [`DECISIONS.md`](./ai/DECISIONS.md) — decisiones tomadas en sentido contrario a la propuesta de la IA
- [`prompts/`](./ai/prompts/) — prompts y conversaciones más relevantes del proceso
- [`skills/`](./ai/skills/) — instrucciones y reglas de comportamiento del agente

En la carpeta [`.github`]:

- [`copilot-instructions.md`](.github/copilot-instructions.md) — fichero con instruciones para el agente de copilot.
