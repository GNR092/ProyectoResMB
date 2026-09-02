# Analisis de Login y Auth (CodeIgniter 4)

## Mapa de rutas (publicas vs protegidas)

### Modo instalacion (sin `writeable/installer.lock`)

Rutas publicas habilitadas:

- `GET /` -> `Installer::index`
- `GET /installer` -> `Installer::index`
- `POST /installer/process` -> `Installer::process`
- `POST /installer/testConnection` -> `Installer::testConnection`
- `GET /installer/migrate` -> `Installer::migrate`

Referencia: `app/Config/Routes.php:12`.

### Modo normal (con `writeable/installer.lock`)

Rutas publicas principales:

- `GET /` -> `Home::index`
- `GET /mantenimiento` -> `Mantenimiento::index`
- `GET /auth` -> `Auth::index`
- `POST /auth/login` -> `Auth::login`
- `GET /auth/logout` -> `Auth::logout`
- `POST /api/gentoken` -> `Api::gentoken`

Referencia: `app/Config/Routes.php:27`.

### Rutas protegidas

La mayor parte de endpoints quedan dentro del grupo:

- `['filter' => ['auth', 'mantenimiento']]`

Referencia: `app/Config/Routes.php:42`.

El control de acceso de autenticacion lo hace `AuthFilter`:

- Si no existe `session('isLoggedIn')`, redirige a `/auth`.

Referencia: `app/Filters/AuthFilter.php:28`.

### Exposicion por auto-routing

No hay exposicion adicional por auto-routing:

- `autoRoute = false`

Referencia: `app/Config/Routing.php:97`.

## Flujo actual de login/auth

1. El formulario de login envia `POST /auth/login`.
2. `Auth::login()` recibe `email`, `password` y `login_as_employee`.
3. Busca usuario por `Correo` y valida hash con `password_verify()`.
4. Selecciona password segun tipo:
   - Jefe: `ContrasenaP`
   - Auxiliar/Empleado: `ContrasenaG`
5. Si autentica correctamente:
   - Genera/actualiza token en `User_Tokens`.
   - Carga datos de usuario/departamento.
   - Guarda sesion (`isLoggedIn`, `id`, `nombre_usuario`, `id_departamento_usuario`, `departamento_usuario`, `login_type`).
   - Redirige a `/`.
6. En logout:
   - Registra bitacora.
   - Nulifica token.
   - Destruye sesion.

Referencias:

- `app/Views/auth/login.php:180`
- `app/Controllers/Auth.php:17`
- `app/Controllers/Auth.php:51`
- `app/Models/UsuariosModel.php:20`
- `app/Controllers/Auth.php:75`
- `app/Controllers/Auth.php:104`
- `app/Models/TokenModel.php:9`

## Hallazgos de riesgo e inconsistencias

- Ruta huérfana: existe `POST /api/gentoken`, pero no se localiza el metodo `Api::gentoken()` en `Api.php`.
- CSRF parcial: el form de login incluye `csrf_field()`, pero el filtro global `csrf` esta comentado.
- Tokens sin enforcement claro: hay tabla/modelo de tokens, pero no se observa validacion Bearer activa en endpoints.
- Logout por `GET`: `GET /auth/logout` es funcional, pero mas expuesto a ejecuciones involuntarias que un `POST` con CSRF.

Referencias:

- `app/Config/Routes.php:35`
- `app/Views/auth/login.php:181`
- `app/Config/Filters.php:81`
- `app/Libraries/Rest.php:48`
- `app/Controllers/Auth.php:123`

## Checklist de hardening minimo (priorizado)

1. **Activar CSRF** en filtros globales o al menos para `POST` criticos.
2. **Regenerar ID de sesion en login exitoso** para mitigar session fixation.
3. **Agregar rate limit y lockout progresivo** en `Auth::login()` contra brute force.
4. **Cambiar logout a `POST` + CSRF** y retirar `GET /auth/logout`.
5. **Resolver `api/gentoken`**: eliminar ruta si no se usa o implementar metodo y validacion completos.
6. **Definir estrategia unica de auth para API**:
   - Sesion/cookie (interna web), o
   - Bearer token real (si hay consumo externo).

## Conclusiones

El sistema actual se autentica principalmente por **sesion** de CodeIgniter, con controles funcionales para acceso interno web. Para fortalecer seguridad de forma inmediata, conviene priorizar CSRF global, endurecimiento del login y cierre de superficies ambiguas (logout por GET y ruta de token huérfana).
