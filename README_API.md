# API GoodOrder (Laravel)

Base URL local: `http://127.0.0.1:8000/api`

## Recursos CRUD

- `universidades`
- `semestres`
- `modulos`
- `horarios`
- `materias`
- `docentes`
- `estudiantes`
- `docente-materias`
- `horas-libres-docentes`
- `inscripciones`

Cada recurso tiene:

- `GET /recurso`
- `GET /recurso/{id}`
- `POST /recurso`
- `PUT/PATCH /recurso/{id}`
- `DELETE /recurso/{id}`

## Endpoints especiales

- `POST /materias/ingesta`
  - Recibe materias creadas en lote usando `updateOrCreate`.
- `GET /horarios/configuracion-ideal`
  - Calcula una configuracion sugerida minimizando puentes por modulo.
- `GET /horarios/exportar`
  - Exporta horarios en `json` (default) o `csv`.

## Ejemplos

### 1) Crear una materia

```bash
curl -X POST http://127.0.0.1:8000/api/materias \
  -H "Content-Type: application/json" \
  -d '{"nombre":"Base de Datos II"}'
```

### 2) Ingesta de materias

```bash
curl -X POST http://127.0.0.1:8000/api/materias/ingesta \
  -H "Content-Type: application/json" \
  -d '{
    "materias": [
      {"nombre":"Programacion I"},
      {"nombre":"Programacion II"},
      {"nombre":"Calculo I"}
    ]
  }'
```

### 3) Configuracion ideal (todos los modulos)

```bash
curl http://127.0.0.1:8000/api/horarios/configuracion-ideal
```

### 4) Configuracion ideal por modulo

```bash
curl "http://127.0.0.1:8000/api/horarios/configuracion-ideal?id_modulo=1"
```

### 5) Exportar horarios CSV

```bash
curl -L "http://127.0.0.1:8000/api/horarios/exportar?formato=csv" -o horarios.csv
```

## Ejecutar proyecto

```bash
php artisan serve
```

## Notas

- La API usa Eloquent con `fillable`, `findOrFail`, `create`, `update`, `destroy`, `updateOrCreate`, relaciones y consultas con `with()`.
- Los modelos usan `timestamps = false` porque tus tablas no tienen `created_at`/`updated_at`.
- Conexion configurada a MySQL `goodorder` con usuario `root` y password vacio en `.env`.
