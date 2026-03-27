# Comandos servidor

```bash
# Limpiar cache de CodeIgniter4
docker exec -it codeigniter_app_fpm php spark cache:clear

# Mostrar ayuda general de Spark
docker exec -it codeigniter_app_fpm php spark list

# ==============================
# Comandos personalizados (app/Commands)
# ==============================

# 1) Generar requisiciones faltantes (no sobrescribe existentes)
docker exec -it codeigniter_app_fpm php spark generar:requisicion-pdf
# Ayuda
docker exec -it codeigniter_app_fpm php spark generar:requisicion-pdf --help

# 2) Regenerar PDFs (requisicion, orden compra y requisicion pago)
docker exec -it codeigniter_app_fpm php spark regenerar:pdfs
# Regenerar en bloques
docker exec -it codeigniter_app_fpm php spark regenerar:pdfs --limit=100 --offset=0
# Regenerar usando un usuario especifico para firma de orden de compra
docker exec -it codeigniter_app_fpm php spark regenerar:pdfs --user-id=1
# Regenerar solo solicitudes especificas (ID_Solicitud separados por coma)
docker exec -it codeigniter_app_fpm php spark regenerar:pdfs --ids=101,102,103
# Ayuda
docker exec -it codeigniter_app_fpm php spark regenerar:pdfs --help

# 3) Modo mantenimiento
# Ver estado
docker exec -it codeigniter_app_fpm php spark maintenance status
# Activar mantenimiento con roles permitidos
docker exec -it codeigniter_app_fpm php spark maintenance on --roles=Administración,Compras
# Activar mantenimiento con mensaje personalizado
docker exec -it codeigniter_app_fpm php spark maintenance on --roles=Administración --message="Estamos en mantenimiento"
# Desactivar mantenimiento
docker exec -it codeigniter_app_fpm php spark maintenance off
# Ayuda
docker exec -it codeigniter_app_fpm php spark maintenance --help

# 4) Correo de prueba
docker exec -it codeigniter_app_fpm php spark email:test
# Ayuda
docker exec -it codeigniter_app_fpm php spark email:test --help

# ==============================
# Git basico
# ==============================
# Cambiar de branch
git checkout "nombre de la branch"
# Actualizar
git pull
# Forzar actualizacion borra datos
git pull --rebase
```
