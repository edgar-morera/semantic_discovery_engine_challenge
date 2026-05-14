# Prompts clave

Registro de los prompts más relevantes usados durante el desarrollo. Se incluye uno por fase, con su contexto.

---

## Infraestructura Docker

> Necesito el stack Docker para una app api rest Symfony 7 + PHP 8.3.
>
> Los servicios que necesito son: PHP-FPM, Nginx, MySQL 8, Qdrant (vector DB).
>
> Algunas cosas concretas:
>
> - El Dockerfile de PHP solo las extensiones necesarias.
> - Composer incluido en la imagen.
> - El usuario dentro del contenedor PHP debe ser www-data con UID 1000.
> - Nginx estandar de symfony.
> - Los ficheros de configuración o de persistencia de los servicios de docker deberan alojarse en una carpeta .docker con subcarpeta a cada servicio.
> - Los datos de mysql y de qdrant tendrán que estar persistidos.
> - Generar .env con las variables de entorno usadas en un desarrollo con docker y symfony.
>
> Genera: Dockerfile, docker-compose.yml, nginx/default.conf y un .env con las variables normalmente usadas para docker y symfony.

---

## Infraestructura: HuggingFace + Qdrant

> Procedemos con la capa de infraestructura.
> - Se va a usar Qdrant para guardar el embedding.
> - No usar cliente Qdrant, usar la una llamada http client.
> - Se usará HuggingFace como servicio de generación de embeddings.
> - Endpoint del modelo: `https://router.huggingface.co/hf-inference/models/ibm-granite/granite-embedding-97m-multilingual-r2`
> - Documentación de HuggingFace y del modelo en https://huggingface.co/docs/inference-providers/tasks/feature-extraction y https://huggingface.co/ibm-granite/granite-embedding-97m-multilingual-r2
>
> Generar tests, solo unitarios o de integración

---

## Caso de uso: buscar producto

>
> Necesito la capa de aplicación para el caso de uso "buscar producto". Haremos uso del patrón CQRS estricto.
>
> Crea los siguientes archivos:
> - SearchProductsQuery.php: Campos queryText y limit (10 por defecto).
> - SearchProductsQueryHandler.php
> - SearchProductsResponse.php: DTO de salida
>
> Generar tests

---

## Comando para tomar productos de Siroko

> Comando symfony para añadir ejemplos de la web de siroko
>
> Necesito crear un comando para extraer información de productos reales de la web de siroko.
>
> Cosideraciones:
> - La url de la api de siroko para obtener productos es `GET https://eu1.apisearch.cloud/v1/as-eefb6b27-6f47-4bed/indices/eu1-5bc10ed7-7fc0-481a
>   ?token=eu1-323045ba-c18d-4843-a38b-131650328ecb
>   &query=<URL-encoded JSON>`.
> - JSON query: `{"fields":["metadata","indexed_metadata"],"size":40,"page":0,"metadata":{"site":"es","language":"es"}}`.
> - Incrementa page (0-indexed). `Respuesta: {"items":[...], "total_items": N}`.
> - Máximo 10 páginas.
> - Mínimo 1 producto por categoría y un máximo de 8.
> - Para la semantic description tiene que usar prosa natural en español con el formato `{title} es {article} {category} para {gender}. Disponible en {colors}. Confeccionado en {materials}.`.
> - Los productos extraidos insertalos en el fichero command, en una constante tipo array con los datos del producto para luego iterar para crear e indexar.
> - Primero crea el producto y después lo indexas.
> - Al final: "Creados: X | Indexados: X | Errores: X".

---

## Performance

> - Instalar k6 en un servicio nuevo del docker compose.
> - Este servicio no se levantará por defecto, solo a petición explicita.
> - Medir performace con k6.
> - Separar scripts por endpoint.
> - Guardar resultados en k6/results/ con --out json=results/load-$(date).json para comparar entre runs. Añadir esta carpeta en el gitignore.
> - Añade comando al makefile para ejecutar el análisis.
>
> Para finalizar ejecutar medir la performance, analizar los resultados.
