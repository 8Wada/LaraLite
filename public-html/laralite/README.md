# CLI Tool para Generación de Estructura PHP Modular

Este CLI automatiza la creación de archivos y carpetas en aplicaciones PHP, usando una estructura modular por entidad y soporte para migraciones de base de datos.

## Estructura Esperada de una Entidad

```
App/
├── Controllers/
├── Models/
├── Routes/
├── Database/
│   ├── Migrations/
│   └── Seeders/
├── Config/
├── Resources/
├── Tests/
└── Helpers/
Users/
├── Controllers/
├── Models/
└── Routes/
Blog/
├── Controllers/
├── Models/
└── Routes/
```

## Comandos Disponibles

### `make:model <Nombre> [Entidad]`
Crea un modelo Eloquent.

- **Por defecto:** `App/Models/<Nombre>.php`
- **Con entidad:** Si se usa `Blog/Post`, va a `./Blog/Models/Post.php`
- Genera una clase que extiende `Illuminate\Database\Eloquent\Model` con tabla en plural (ejemplo: `posts` para `Post`).

### `make:controller <Nombre> [Entidad]`
Crea un controlador PHP.

- **Por defecto:** `App/Controllers/<Nombre>Controller.php`
- **Con entidad:** Si se usa `Blog/Post`, va a `./Blog/Controllers/PostController.php`
- Incluye un método `index` vacío.

### `make:route <Nombre> [Entidad]`
Crea un archivo de rutas.

- **Por defecto:** `App/Routes/<Nombre>.php`
- **Con entidad:** Si se usa `Blog/Web`, va a `./Blog/Routes/Web.php`
- Define una ruta básica usando `App\Router`.

### `make:entity <Nombre>`
Crea una estructura completa para una entidad:

- **Directorios:** `./<Nombre>/Controllers`, `./<Nombre>/Models`, `./<Nombre>/Routes`
- **Archivos iniciales:**
  - `./<Nombre>/Models/Main.php` (modelo Eloquent)
  - `./<Nombre>/Controllers/MainController.php` (controlador)
  - `./<Nombre>/Routes/web.php` (ruta base)

### `make:seed <Nombre>`
Crea un seeder.

- **Ubicación:** `App/Database/Seeders/<Nombre>Seeder.php`
- Incluye un método `run` con ejemplo de inserción.

### Migraciones

- `migration:up`: Ejecuta todas las migraciones en `App/Database/Migrations/`.
- `migration:down`: Elimina la última migración.
- `migration:rollback <count>`: Elimina las últimas `<count>` migraciones (por defecto, 1).
- `migration:status`: Lista todas las migraciones en `App/Database/Migrations/`.

### Base de Datos

- `db:info`: Muestra la información de conexión a la base de datos.
- `db:migrate`: Alias de `migration:up`.
- `db:rollback <count>`: Alias de `migration:rollback`.
- `db:status`: Alias de `migration:status`.

## Ejemplos de Uso

```bash
php script.php make:model User
php script.php make:model Blog/Post Blog
php script.php make:controller Blog/PostController Blog
php script.php make:route Web Blog
php script.php make:entity Shop
php script.php make:seed User
php script.php migration:up
php script.php migration:rollback 2
php script.php db:status
```

## Notas

- **Validación:** Si falta un argumento requerido (como `<Nombre>`), muestra un mensaje de error y termina.
- **Estructura automática:** Crea directorios necesarios con permisos `0755`.
- **Namespaces:** Se adaptan automáticamente según la entidad (ejemplo: `Blog\Models` para `./Blog/Models`).
- **Migraciones:** Requieren configuración en `config/config.php` y `App\Database\EloquentBootstrap`.
- **Rutas:** Asumen la existencia de `App\Router` para las rutas generadas.
- **Errores:** Comandos no reconocidos o argumentos inválidos generan mensajes claros y terminan el script.
