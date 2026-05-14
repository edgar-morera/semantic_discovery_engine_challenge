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



