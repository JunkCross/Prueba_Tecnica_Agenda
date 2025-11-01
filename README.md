# Prueba técnica – Calendario (Symfony + PostgreSQL + Vue + Docker)

## Tecnologías
- PHP 8.2 + Apache (Symfony Components: HttpFoundation/Routing)
- PostgreSQL 15
- Vue 3 + FullCalendar (resource timeline)
- Docker y docker-compose

## Estructura
- `docker-compose.yml`
- `api/`
  - `Dockerfile`
  - `apache-vhost.conf`
  - `public/index.php`
  - `public/.htaccess`
  - `composer.json`
- `frontend/`
  - `Dockerfile`
  - `package.json`
  - `vite.config.js`
  - `calendar.css`
  - `src/App.vue`, `src/main.js`
- `init.sql` (estructura de la tabla `schedules`)

## URLs del proyecto

```bash
- Frontend: http://localhost:5173/
- API: http://localhost:8000/api/schedules
```

## Levantar el proyecto
```bash
docker compose up --build
```



