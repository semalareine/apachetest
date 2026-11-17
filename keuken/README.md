# Keuken

The project contains a Vite/React kitchen scene and a PHP recipe book. The
recipe book stores recipes in SQLite under `data/recipes.sqlite`; the PHP
server must have PDO SQLite enabled.

## Local development

```sh
npm install
npm run dev
```

The dev command starts PHP on `127.0.0.1:8000` and Vite, which proxies the PHP
pages. Open the Vite URL printed in the terminal. If PHP is not on your `PATH`,
set `PHP_BINARY` to the PHP executable before running `npm run dev`.

## Apache deployment

The app requires a web host that runs PHP and has both `PDO` and `pdo_sqlite`
enabled; static-only hosting is not sufficient. To create a complete
deployment directory, run:

```sh
npm install
npm run build
```

Upload the **contents** of `dist/` to the domain's Apache document root,
including `index.html`, `assets/`, `src/`, and `data/`. The build includes the
PHP pages and an Apache rule that denies web access to the SQLite data
directory. Use a host that honors `.htaccess` in `data/` (Apache 2.4 with
`AllowOverride AuthConfig`); do not use this layout on Nginx, which ignores
`.htaccess`.

Enable PHP for `.php` files and PDO SQLite on the host. PHP must be able to
write to `data/` so it can create and update `recipes.sqlite`; grant write
access to the PHP process without making the directory publicly accessible.
The app initializes the database and sample recipes on first request.

User registration is open by default. To provision the reserved administrator
account, set the server environment variable `ADMIN_PASSWORD` to a unique
password of at least 12 characters before the first request. The admin
username is `alexcaspercrud`. Keep this secret out of the web root and source
control, and use HTTPS in production.
