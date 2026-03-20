# Job Simulator API

API REST CRUD construida con PHP, PostgreSQL y Docker.

## Requisitos

- Docker
- Docker Compose

## Inicio rapido

El proyecto puede levantarse sin archivo `.env`. `docker-compose.yml` ya incluye valores por defecto seguros para desarrollo.

```bash
docker compose up -d --build
```

La API quedara disponible en:

```text
http://localhost:8000
```

La base de datos PostgreSQL quedara publicada en:

```text
localhost:5433
```

Si quieres personalizar puertos o credenciales, puedes crear un `.env` local a partir de `.env.example`.

## Reinicio limpio

El script `sql/init.sql` se ejecuta automaticamente cuando PostgreSQL inicializa un volumen nuevo. Si quieres reconstruir la base desde cero:

```bash
docker compose down -v
docker compose up -d --build
```

## Estructura

```text
app/
  config/
  controllers/
  models/
  routes/
public/
sql/
docker/
```

## Recurso

La API expone el recurso `resources` con esta estructura:

```json
{
  "id": 1,
  "campo1": "string",
  "campo2": "string",
  "campo3": "string",
  "campo4": 10,
  "campo5": 12.5,
  "campo6": true
}
```

Reglas:

- `campo1`, `campo2` y `campo3` deben ser strings no vacios
- `campo4` debe ser integer
- `campo5` debe ser float/decimal
- `campo6` debe ser boolean
- todos los campos son requeridos en `POST` y `PUT`
- `PATCH` acepta solo los campos enviados

## Endpoints

### `GET /resources`

Lista todos los registros.

Respuesta `200`:

```json
[
  {
    "id": 1,
    "campo1": "A",
    "campo2": "B",
    "campo3": "C",
    "campo4": 10,
    "campo5": 12.5,
    "campo6": true
  }
]
```

### `GET /resources/{id}`

Obtiene un registro por id.

Respuesta `200`:

```json
{
  "id": 1,
  "campo1": "A",
  "campo2": "B",
  "campo3": "C",
  "campo4": 10,
  "campo5": 12.5,
  "campo6": true
}
```

Respuesta `404`:

```json
{
  "error": "Resource not found."
}
```

### `POST /resources`

Crea un registro.

Body:

```json
{
  "campo1": "A",
  "campo2": "B",
  "campo3": "C",
  "campo4": 10,
  "campo5": 12.5,
  "campo6": true
}
```

Respuesta `201`:

```json
{
  "id": 1,
  "campo1": "A",
  "campo2": "B",
  "campo3": "C",
  "campo4": 10,
  "campo5": 12.5,
  "campo6": true
}
```

### `PUT /resources/{id}`

Actualiza completamente un registro. Requiere todos los campos.

Body:

```json
{
  "campo1": "A2",
  "campo2": "B2",
  "campo3": "C2",
  "campo4": 20,
  "campo5": 21.75,
  "campo6": false
}
```

Respuesta `200`:

```json
{
  "id": 1,
  "campo1": "A2",
  "campo2": "B2",
  "campo3": "C2",
  "campo4": 20,
  "campo5": 21.75,
  "campo6": false
}
```

### `PATCH /resources/{id}`

Actualiza parcialmente un registro.

Body:

```json
{
  "campo3": "C3",
  "campo6": true
}
```

Respuesta `200`:

```json
{
  "id": 1,
  "campo1": "A2",
  "campo2": "B2",
  "campo3": "C3",
  "campo4": 20,
  "campo5": 21.75,
  "campo6": true
}
```

### `DELETE /resources/{id}`

Elimina un registro.

Respuesta `200`:

```json
{
  "message": "Resource deleted successfully."
}
```

## Codigos HTTP

- `200` solicitud exitosa
- `201` recurso creado
- `400` JSON invalido o request mal formado
- `404` recurso no encontrado
- `405` metodo no permitido
- `422` error de validacion
- `500` error interno o de base de datos

## Ejemplos de validacion

Body incompleto:

```json
{
  "campo1": "A",
  "campo2": "B"
}
```

Respuesta `422`:

```json
{
  "error": "Missing required fields: campo3, campo4, campo5, campo6"
}
```

Tipo invalido:

```json
{
  "campo1": "A",
  "campo2": "B",
  "campo3": "C",
  "campo4": "10",
  "campo5": 12.5,
  "campo6": true
}
```

Respuesta `422`:

```json
{
  "error": "Field campo4 must be an integer."
}
```

## Variables de entorno

El proyecto soporta estas variables:

```text
APP_NAME
APP_PORT
APP_ENV
DB_CONNECTION
DB_HOST
DB_PORT
DB_FORWARD_PORT
DB_NAME
DB_USER
DB_PASSWORD
```

Si no defines ninguna, Docker Compose usara los defaults del proyecto.
