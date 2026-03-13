# Prompt para Claude AI - SEO + AI Discovery para LinuxCMS

Este documento esta pensado para tu flujo real:

- Tu generas una web base con LM Studio y capsulas premium.
- Luego quieres pasar esa web por Claude para mejorar SEO local y visibilidad en buscadores y answer engines.
- El resultado debe seguir siendo compatible con LinuxCMS. No queremos un HTML nuevo e independiente.

La regla principal es esta:

No le pidas a Claude que "rediseñe" la landing ni que te devuelva una web nueva.
Pidele un parche de SEO + AI discovery sobre la estructura existente.

## 1. Que pasarle a Claude

Cuando le pases una web a Claude, dale:

- La URL publica si ya existe, o el HTML exportado si aun esta local.
- Los datos del negocio.
- La ciudad y el area local objetivo.
- Los servicios principales.
- Los precios reales que si se pueden publicar.
- El CTA principal.
- Las claims permitidas.
- Si hay blog, horarios, reservas, menu o lista de precios.

No le des solo "haz SEO". Dale hechos concretos.

## 2. Prompt recomendado

Usa este prompt. Sustituye los corchetes.

```text
Quiero que mejores esta landing page para SEO local y AI discovery sin cambiar su diseno premium ni su estructura general de capsulas.

CONTEXTO
- CMS: LinuxCMS
- Tipo de pagina: landing page local
- Objetivo: mejorar visibilidad en Google y en buscadores con IA como ChatGPT Search y Perplexity
- Importante: NO rehagas la web ni cambies el look. NO devuelvas una web nueva. Devuelveme un parche SEO/AI utilizable.

DATOS DEL NEGOCIO
- Nombre: [Casa Maria]
- Tipo: [Restaurante]
- Ciudad: [Valencia]
- Zona o barrio: [Centro historico, junto a la Lonja]
- Direccion: [Calle Mayor 15, 46001 Valencia]
- Telefono: [+34 963 123 456]
- Email: [info@casamaria.com]
- CTA principal: [Reservar mesa]
- Horario: [L-V 13:00-16:00 y 20:00-23:00, S 13:00-16:00, D cerrado]
- Servicios principales: [menu del dia, cocina casera, eventos privados]
- Precios publicables: [menu del dia 11.50 EUR, carta 15-25 EUR]
- Reservas: [si, por telefono y formulario]
- Web o URL canonical: [https://casamaria.com/]

PALABRAS CLAVE Y BUSQUEDA LOCAL
- Keyword principal: [restaurante en Valencia centro]
- Keywords secundarias: [menu del dia Valencia, cocina casera Valencia, restaurante cerca de la Lonja]
- Intencion: [comercial local]

REGLAS
1. No cambies el diseno visual.
2. No inventes premios, resenas, cifras, clientes ni claims no verificadas.
3. No me devuelvas HTML completo reescrito.
4. Devuelveme SOLO un paquete SEO/AI en JSON.
5. Todo lo que propongas debe ser visible en pagina si afecta a FAQ, precios, horarios o direccion.
6. Si falta informacion, marca el campo como "TODO" en vez de inventarlo.

QUIERO ESTE OUTPUT JSON EXACTO:
{
  "meta": {
    "title": "",
    "meta_description": "",
    "canonical_url": "",
    "og_title": "",
    "og_description": "",
    "og_type": "",
    "og_locale": "",
    "og_image_hint": "",
    "twitter_card": ""
  },
  "business_profile_patch": {
    "type": "",
    "name": "",
    "description": "",
    "street_address": "",
    "postal_code": "",
    "city": "",
    "region": "",
    "country": "",
    "phone": "",
    "email": "",
    "price_range": "",
    "currencies_accepted": "",
    "serves_cuisine": "",
    "reservation_url": "",
    "menu_url": "",
    "latitude": "",
    "longitude": ""
  },
  "visible_copy_patch": {
    "hero_local_sentence": "",
    "price_sentence": "",
    "hours_sentence": "",
    "location_sentence": "",
    "trust_sentence": ""
  },
  "faq_items": [
    { "question": "", "answer": "" }
  ],
  "internal_links": [
    { "anchor": "", "target": "" }
  ],
  "alt_text_map": [
    { "image_hint": "", "alt": "" }
  ],
  "json_ld": {
    "website": {},
    "local_business": {},
    "faq_page": {},
    "breadcrumb": {}
  },
  "ai_json": {},
  "qa_notes": [
    ""
  ]
}

OBJETIVO DEL CONTENIDO
- La pagina debe responder claramente en texto visible:
  - que es el negocio
  - donde esta
  - que ofrece
  - cuanto cuesta
  - cual es el horario
  - como reservar o contactar
- La salida debe estar pensada para Google y para answer engines.
```

## 3. Version corta

Si quieres ir rapido, usa esta version:

```text
Mejora esta landing page para SEO local y AI discovery sin cambiar su diseno.

Negocio: [Casa Maria]
Tipo: [Restaurante]
Ciudad: [Valencia]
Direccion: [Calle Mayor 15, 46001 Valencia]
Telefono: [+34 963 123 456]
Horario: [L-V 13-16 y 20-23, S 13-16, D cerrado]
Precios: [menu 11.50 EUR, carta 15-25 EUR]
CTA: [Reservar mesa]
Canonical: [https://casamaria.com/]

Devuelveme SOLO:
- title
- meta description
- business profile patch
- 5 FAQ reales
- 4 frases visibles para hero/precios/horario/ubicacion
- JSON-LD de website + local business + faq + breadcrumb
- contenido de /.well-known/ai.json

No cambies el look ni la estructura visual.
No inventes datos.
```

## 4. Prompt para posts de blog

```text
Optimiza este post de LinuxCMS para SEO y AI discovery.

NEGOCIO
- Nombre: [Casa Maria]
- Tipo: [Restaurante]
- Ciudad: [Valencia]
- URL del post: [https://casamaria.com/blog/nueva-carta-de-invierno]

OBJETIVO
- Captar trafico local y de intencion informacional.
- Mantener el tono del negocio.

PALABRAS CLAVE
- Principal: [nueva carta de invierno restaurante valencia]
- Secundarias: [platos de temporada valencia, cocina casera invierno valencia]

DEVUELVEME SOLO JSON CON:
{
  "meta_title": "",
  "meta_description": "",
  "h1": "",
  "h2_suggestions": ["", "", ""],
  "intro_paragraph": "",
  "cta_paragraph": "",
  "internal_links": [
    { "anchor": "", "target": "" }
  ],
  "alt_text_map": [
    { "image_hint": "", "alt": "" }
  ],
  "json_ld": {
    "blog_posting": {}
  }
}

No inventes hechos no publicados por el negocio.
```

## 5. Que debe generar LinuxCMS automaticamente

Esto es lo que conviene implementar en el CMS, alineado con el estado actual del codigo.

### Ya existe en el repo

En este arbol ya tienes:

- Canonical URL basica en [src/render.php](/Users/c/Desktop/2/videojuego/linuxcms/src/render.php)
- Open Graph y Twitter basicos en [src/render.php](/Users/c/Desktop/2/videojuego/linuxcms/src/render.php)
- JSON-LD de pagina/post + negocio en [src/render.php](/Users/c/Desktop/2/videojuego/linuxcms/src/render.php)
- Sitemap XML basico en [src/render.php](/Users/c/Desktop/2/videojuego/linuxcms/src/render.php)
- `robots.txt` basico en [src/render.php](/Users/c/Desktop/2/videojuego/linuxcms/src/render.php)
- Feed `/.well-known/ai.json` via [ai-json.php](/Users/c/Desktop/2/videojuego/linuxcms/ai-json.php)
- Cache `ETag` + `Cache-Control` en [index.php](/Users/c/Desktop/2/videojuego/linuxcms/index.php)
- Business profile y live data en [src/storage.php](/Users/c/Desktop/2/videojuego/linuxcms/src/storage.php) y [src/render.php](/Users/c/Desktop/2/videojuego/linuxcms/src/render.php)

### Lo siguiente que falta o merece mejora

Prioridad 1:

- Completar Open Graph y Twitter con:
  - `og:locale`
  - `og:image:width`
  - `og:image:height`
  - `article:modified_time` en posts
- Añadir un helper robusto para detectar imagen principal real de la pagina
- Mejorar `robots.txt` para no bloquear crawlers utiles y mantener fuera `/r-admin/` y `/mi-negocio/`
- Hacer que la pagina tenga hechos visibles en texto, no solo en JSON-LD:
  - direccion
  - horario
  - precio base
  - CTA local

Prioridad 2:

- Extender el grafo JSON-LD con:
  - `Organization` o `WebSite`
  - `BreadcrumbList`
  - `FAQPage` cuando aplique
- Completar `LocalBusiness` con:
  - `image`
  - `priceRange`
  - `servesCuisine`
  - `acceptsReservations`
  - `makesOffer` o `hasOfferCatalog` segun el caso
- Mejorar `sitemap.xml` para incluir imagenes del contenido cuando existan

Prioridad 3:

- Introducir un bloque o helper de "local facts" que pinte en pagina:
  - negocio
  - barrio
  - direccion
  - horario
  - precio desde
  - reservas
- Añadir sugerencias SEO al flujo de Studio para que LM Studio y Claude no generen copy vacio de contexto local

## 6. Lo que NO conviene pedir ni prometer

No plantees esto como si garantizara "primeros resultados".

Mejor lenguaje:

- "mejorar indexacion"
- "mejorar entendimiento del negocio por Google"
- "mejorar discoverability para answer engines"
- "facilitar citacion y crawling"

No conviene vender como estandar universal:

- `/.well-known/ai.json`

Eso puede ser muy util para LinuxCMS, pero hoy debes tratarlo como feed propietario del CMS, no como estandar oficial de Google o OpenAI.

## 7. Nota importante sobre FAQ

Puedes seguir generando FAQ para comprension y para answer engines, pero no debes prometer rich results de FAQ en cualquier vertical.

Segun Google, los rich results de `FAQPage` estan limitados a sitios de salud y gobierno bien conocidos. Aun asi, el marcado puede seguir siendo util para estructura semantica si el contenido FAQ esta visible en pagina.

## 8. Recomendacion de implementacion en LinuxCMS

Si luego se implementa en codigo, el orden correcto seria:

1. Fortalecer head metadata en [src/render.php](/Users/c/Desktop/2/videojuego/linuxcms/src/render.php)
2. Extender `ccms_render_public_schema()` con breadcrumb y FAQ opcional
3. Mejorar `ccms_render_sitemap_xml()` para imagenes
4. Mejorar `ccms_render_robots_txt()` para crawling y zonas privadas
5. Anadir "local facts" visibles a la generacion IA en [src/ai.php](/Users/c/Desktop/2/videojuego/linuxcms/src/ai.php)
6. Anadir checklist SEO en Studio y/o Site Settings

## 9. Checklist rapido

- `title` con negocio + servicio + ciudad
- `meta description` clara y accionable
- canonical correcto
- OG/Twitter completos
- `LocalBusiness` correcto
- direccion visible en pagina
- horario visible en pagina
- precios visibles en pagina
- FAQ visibles cuando aporten valor
- sitemap actualizado
- robots correcto
- `ai.json` coherente con business profile

## 10. Fuentes oficiales a seguir

- Google Search Central - Local Business:
  [developers.google.com/search/docs/appearance/structured-data/local-business](https://developers.google.com/search/docs/appearance/structured-data/local-business)
- Google Search Central - Canonical URLs:
  [developers.google.com/search/docs/crawling-indexing/consolidate-duplicate-urls](https://developers.google.com/search/docs/crawling-indexing/consolidate-duplicate-urls)
- Google Search Central - Image sitemaps:
  [developers.google.com/search/docs/crawling-indexing/sitemaps/image-sitemaps](https://developers.google.com/search/docs/crawling-indexing/sitemaps/image-sitemaps)
- Google Search Central - Image SEO:
  [developers.google.com/search/docs/appearance/google-images](https://developers.google.com/search/docs/appearance/google-images)
- Google Search Central - FAQPage:
  [developers.google.com/search/docs/appearance/structured-data/faqpage](https://developers.google.com/search/docs/appearance/structured-data/faqpage)
- Google Search Central - Article structured data:
  [developers.google.com/search/docs/appearance/structured-data/article](https://developers.google.com/search/docs/appearance/structured-data/article)
- Google Search Central - Breadcrumb structured data:
  [developers.google.com/search/docs/appearance/structured-data/breadcrumb](https://developers.google.com/search/docs/appearance/structured-data/breadcrumb)
- OpenAI Help - ChatGPT Search:
  [help.openai.com/en/articles/9237897-chatgpt-search](https://help.openai.com/en/articles/9237897-chatgpt-search)

## 11. Resumen practico

Si le vas a pasar una web a Claude, lo correcto es pedirle:

- metadatos
- schema
- hechos visibles
- FAQ
- `ai.json`
- alt text
- enlaces internos

No le pidas:

- un HTML entero nuevo
- una reescritura total del diseno
- claims inventadas

Para LinuxCMS, Claude debe actuar como capa de enrichment SEO/AI sobre una landing premium ya creada, no como reemplazo del renderer ni del builder.
