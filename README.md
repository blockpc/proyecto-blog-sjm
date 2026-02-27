# Proyecto Blog SJM (Plantilla Blockpc)

Aplicación Laravel 12 con Livewire 4 + Flux UI, autenticación con Fortify y gestión de roles/permisos sobre Spatie Permission.

## Stack técnico

- PHP 8.2+
- Laravel 12
- Livewire 4
- Flux UI (free)
- Tailwind CSS 4 + Vite
- MariaDB (vía Sail)
- Pest para testing

## Requisitos

- Docker + Docker Compose (recomendado, usando Sail)
- Node.js + npm
- Composer

## Instalación rápida (recomendada con Sail)

1. Clonar el repositorio.
2. Instalar dependencias PHP:

	```bash
	composer install
	```

3. Crear `.env`:

	```bash
	cp .env.example .env
	```

4. Levantar contenedores:

	```bash
	./vendor/bin/sail up -d
	```

5. Generar clave y preparar base de datos:

	```bash
	./vendor/bin/sail artisan key:generate
	./vendor/bin/sail artisan migrate
	```

6. Instalar dependencias frontend y compilar assets:

	```bash
	npm install
	npm run build
	```

## Instalación alternativa (sin Docker)

Si trabajas sin Sail, puedes usar el script de setup:

```bash
composer run setup
```

## Desarrollo

- Con entorno local tradicional:

  ```bash
  composer run dev
  ```

  Este comando levanta servidor HTTP, cola y Vite en paralelo.

- Con Sail (comandos más usados):

  ```bash
  ./vendor/bin/sail up -d
  ./vendor/bin/sail artisan migrate
  ./vendor/bin/sail artisan test
  ```

## Rutas principales

- `/` → vista de bienvenida
- `/dashboard` → dashboard (requiere usuario autenticado y verificado)
- `/settings/profile` → edición de perfil
- `/settings/password` → cambio de contraseña
- `/settings/appearance` → apariencia
- `/settings/two-factor` → configuración 2FA (según features habilitadas en Fortify)

## Roles y permisos (Blockpc)

El proyecto incluye listas centralizadas para sincronizar catálogo base:

- Roles: `blockpc/App/Lists/RoleList.php`
- Permisos: `blockpc/App/Lists/PermissionList.php`

Seeders relacionados:

- `Database\\Seeders\\RolesAndPermissionsSeeder`

Comandos disponibles:

### Sincronizar permisos

```bash
./vendor/bin/sail artisan blockpc:permissions
```

Opciones:

- `--check`: valida faltantes
- `--orphans`: muestra huérfanos
- `--prune`: elimina huérfanos
- `--ci`: evita confirmación interactiva con `--prune`

### Sincronizar roles

```bash
./vendor/bin/sail artisan blockpc:roles
```

Opciones:

- `--check`: valida faltantes
- `--orphans`: muestra huérfanos
- `--prune`: elimina huérfanos editables
- `--ci`: evita confirmación interactiva con `--prune`

Importante: en ambos comandos, `--check`, `--orphans` y `--prune` son mutuamente excluyentes.

## Testing y calidad

Ejecutar tests:

```bash
./vendor/bin/sail artisan test
```

Ejecutar formato:

```bash
vendor/bin/pint --dirty
```

Scripts de Composer útiles:

- `composer run test`
- `composer run lint`
- `composer run test:lint`

## Estructura personalizada Blockpc

- `blockpc/App/Commands`: comandos Artisan propios
- `blockpc/App/Services`: servicios de sincronización
- `blockpc/App/Lists`: catálogos de roles/permisos
- `blockpc/App/Mixins/Search.php`: mixin para búsquedas tipo `LIKE` en Eloquent Builder

## Notas

- Los comandos de roles/permisos se registran desde `BlockpcServiceProvider`.
- Si no ves cambios frontend, ejecuta `npm run dev` o `npm run build`.

