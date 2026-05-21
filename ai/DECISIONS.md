# DECISIONS.md — Decisiones frente a la IA

Registro de algunos momentos donde la IA propuso algo y se decidió en sentido contrario basándose en criterio senior.

---

## 1. Doctrine attributes en Domain

**IA propuso:** Añadir `#[ORM\Entity]` y atributos de mapeo directamente en las clases de dominio.

**Decisión:** Rechazado. Introduce una dependencia de Doctrine en el Domain, violando la Arquitectura Hexagonal. Se optó por XML mapping en `Infrastructure/Persistence/`.

---

## 2. Alucinación en diagnóstico de mapping Doctrine

**IA propuso:** Sucesivas hipótesis sobre la configuración de Doctrine (bundle, driver, `auto_mapping`) sin identificar el problema real tras varios intentos.

**Decisión:** El dev interrumpió el loop de sugerencias e identificó directamente que el fichero de mapping XML tenía nombre incorrecto — le faltaba la extensión `.orm.xml` — lo que impedía que Doctrine lo detectara. La IA no llegó a esa conclusión por sí sola.

---

## 3. `semanticDescription` con validación estricta (no nullable)

**IA propuso:** Tratar `semanticDescription` igual que `ProductName` — obligatorio en creación.

**Decisión:** Se mantuvo como nullable deliberadamente. El ciclo de vida del producto contempla crearlo primero e indexar después — dos operaciones desacopladas.

---

## 4. Score de similitud no expuesto en la respuesta

**IA propuso:** Devolver solo los productos ordenados sin incluir el score de similitud coseno.

**Decisión:** Se exigió incluir el score en el VO `SearchResult` y en el DTO de respuesta. Es información relevante para el consumidor de la API y para evaluar la calidad del ranking semántico.

---

## 5. NelmioApiDocBundle sin dependencias de Twig/Asset

**IA configuró** NelmioApiDocBundle correctamente pero omitió `symfony/twig-bundle` y `symfony/asset`, necesarios para renderizar la UI de `/api/doc`.

**Decisión:** Detectado en runtime al intentar acceder a la documentación. Se instalaron las dependencias faltantes.

---

## 6. `REDIS_DSN` no propagada al contenedor PHP

**IA configuró** el servicio Redis en docker-compose y `cache.yaml` correctamente, pero no incluyó `REDIS_DSN` disponible en el fichero .env en el bloque `environment:` del servicio `php`.

**Decisión:** Se añadió la variable al bloque `environment:` del servicio.

---

## 7. Embedding desacoplado del agregado Product

**IA generó** `Product` con una propiedad `$embedding` nullable, métodos `assignEmbedding()`, `isIndexed()` y `embedding()`, y el handler mutando el agregado tras generar el vector.

**Decisión:** Rechazado. El embedding es un artefacto de la infraestructura de búsqueda semántica, no un atributo del agregado de negocio. Además, Doctrine nunca persiste `$embedding` (vive en Qdrant), por lo que `isIndexed()` siempre devolvía `false` tras hidratar desde MySQL. Se eliminaron los métodos del agregado y el handler ahora pasa el `Embedding` directamente a `ProductSearchPort::index()` sin mutar el producto.

---

## 9. Datos del producto leídos del payload de Qdrant en la búsqueda

**IA generó** `SearchProductsQueryHandler` haciendo una consulta MySQL (`findById`) por cada resultado de Qdrant para obtener nombre y descripción.

**Decisión:** Rechazado el patrón N+1. `SearchResult` se enriqueció con `name` y `semanticDescription` leídos directamente del payload de Qdrant, eliminando la dependencia de MySQL en el flujo de búsqueda. La consistencia eventual es el comportamiento esperado en un sistema de búsqueda — los datos se actualizan al re-indexar.

---

## 8. UUID generado en el controlador mediante puerto de dominio

**IA generó** `CreateProductCommand` con auto-generación de UUID en el constructor (`Uuid::v4()` como side effect).

**Decisión:** Rechazado. Un command es un DTO puro — no debe tener lógica ni dependencias de infraestructura. Se introdujo el puerto `ProductIdGenerator` en el dominio con el adaptador `SymfonyUuidProductIdGenerator` en infraestructura. El controlador inyecta el puerto, genera el ID en el borde del sistema y lo pasa al command como dato estable. El handler permanece `void`.

