<!-- Logo -->
<p align="center">
    <img src="./assets/logo (3).png" alt="LaraLite Logo" width="200"/>
</p>

# Plantilla de despliegue | LaraLite v1.0

Este repositorio contiene una plantilla de despliegue para aplicaciones web desarrolladas con LaraLite v1.0. La plantilla está diseñada para facilitar la configuración y el despliegue de aplicaciones en entornos de producción.

## Estructura del Repositorio

La carpeta donde se encuentra el proyecto principal es `public_html`. Dentro de esta carpeta, encontrarás los siguientes archivos y directorios:

- `front/`: Contiene los archivos del frontend de la aplicación. (Puede ser vanilla JS, o builders como React, Vue, Angular, etc.)
- `laralite/`: Contiene los archivos del backend de la aplicación.
- `simplesaml/`: Contiene los archivos relacionados con la autenticación SAML.
- `Dockerfile`: Archivo de configuración para la creación de la imagen Docker.

## Instrucciones de Despliegue

Para desplegar la aplicación utilizando esta plantilla, sigue estos pasos:

1. Clona este repositorio en tu máquina local.
2. Copia el `env.example` a un nuevo archivo `.env` y configura las variables de entorno según tus necesidades.
3. Por defecto, el proyecto se llamara `laralite`. Actualiza los nombres en los archivos de configuración acorde al nombre de tu proyecto.
4. Actualiza el archivo `public_html/Dockerfile` si es necesario, para adaptarlo a tu entorno de despliegue.
5. Construye la imagen Docker utilizando el siguiente comando:

  ```bash
  docker compose down -v; docker compose up -d --build --force-recreate
  ```

6. Si tienes migraciones pendientes, ejecútalas con el siguiente comando:

```bash
docker exec -it laralite php /var/www/laralite/laralite/artisano migrate:up
```

7. Una vez finalizadas las migraciones, puedes acceder a la aplicación en tu navegador web en la URL configurada.
En base a la configuración actual, la URL por defecto será `http://localhost/laralite/api/v1/`

8. El framework por defecto cuenta con monitoreo de logs utilizando `logviewer`. Puedes acceder a los logs en la siguiente URL:

`http://localhost/laralite/api/monitor/quick_test_monitor.php` - Para pruebas rápidas de monitoreo.
`http://localhost/laralite/api/monitor/sql_monitor.php` - Para monitoreo de consultas SQL.
