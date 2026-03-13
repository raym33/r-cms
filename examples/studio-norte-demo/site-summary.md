# Studio Norte Demo

Ejemplo completo de web que se puede crear con LinuxCMS.

## Que incluye

- 4 paginas publicas:
  - Inicio
  - Servicios
  - Sobre nosotros
  - Contacto
- 3 posts publicados
- Blog con archivo, categorias, tags y RSS
- Formularios reales de newsletter y contacto
- SEO minimo activo
- Analytics configurado en modo demo
- Export estatico listo para hosting basico

## Credenciales del backup de ejemplo

- usuario: demo
- password: Demo-StudioNorte-2026!

Importante:
- El backup reemplaza sitio, paginas, posts, media y usuarios.
- Importalo en una instalacion de prueba o en una instalacion nueva.

## Archivos principales

- backup importable: `backup.json`
- export estatico: `static-site/`
- zip del export estatico: `static-site.zip`
- runtime aislado de ejemplo: `runtime/`

## Como usarlo

### Ver el export estatico

Abre `static-site/index.html` o sube el contenido de `static-site/` a un hosting basico.

### Importarlo dentro de LinuxCMS

1. Arranca LinuxCMS.
2. Entra en `/r-admin`.
3. Ve a `Backups`.
4. Importa `backup.json`.

### Runtime aislado

El directorio `runtime/` contiene `data/` y `uploads/` del ejemplo. Sirve para pruebas, exportes o desarrollo.
