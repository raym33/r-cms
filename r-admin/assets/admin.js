const bootstrap = window.CCMS_ADMIN_BOOTSTRAP || {};
const sectionTemplates = bootstrap.sectionTemplates || [];
const capsuleBuilderTemplates = bootstrap.capsuleBuilderTemplates || [];
const initialCapsuleState = bootstrap.initialCapsuleState || { meta: {}, style: {}, blocks: [] };
const mediaItems = bootstrap.mediaItems || [];
const previewSiteConfig = bootstrap.previewSiteConfig || { site: {}, menu: [] };
const builderReadOnly = !!bootstrap.builderReadOnly;
const cspNonce = bootstrap.cspNonce || "";

    function insertAtCursor(textarea, snippet) {
      if (!textarea) return;
      const start = textarea.selectionStart ?? textarea.value.length;
      const end = textarea.selectionEnd ?? textarea.value.length;
      const before = textarea.value.slice(0, start);
      const after = textarea.value.slice(end);
      const glue = before && !before.endsWith("\n") ? "\n\n" : "";
      textarea.value = before + glue + snippet + "\n\n" + after;
      const nextPos = before.length + glue.length + snippet.length + 2;
      textarea.focus();
      textarea.selectionStart = textarea.selectionEnd = nextPos;
      textarea.dispatchEvent(new Event("input", { bubbles: true }));
    }

    function buildPreviewDoc(pageTitle, htmlContent) {
      const site = previewSiteConfig.site || {};
      const colors = site.colors || {};
      const menu = Array.isArray(previewSiteConfig.menu) ? previewSiteConfig.menu : [];
      const menuHtml = menu.map((item) => {
        const href = item.is_homepage ? "/" : "/" + encodeURIComponent(item.slug || "");
        const label = item.label || "Página";
        return `<a href="${href}">${label}</a>`;
      }).join("");
      const title = pageTitle || site.title || "LinuxCMS";
      const description = site.tagline || "";
      const styleNonceAttr = cspNonce ? ` nonce="${cspNonce}"` : "";
      return `<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>${title}</title>
  <meta name="description" content="${description}">
  <style${styleNonceAttr}>
    :root{
      --bg:${colors.bg || "#f7f4ee"};
      --surface:${colors.surface || "#ffffff"};
      --text:${colors.text || "#2f241f"};
      --muted:${colors.muted || "#6b5b53"};
      --primary:${colors.primary || "#c86f5c"};
      --secondary:${colors.secondary || "#d9c4b3"};
      --max:1200px;
    }
    *{box-sizing:border-box}
    body{margin:0;background:var(--bg);color:var(--text);font-family:Arial,Helvetica,sans-serif}
    a{color:inherit}
    .shell{width:min(var(--max),calc(100% - 28px));margin:0 auto}
    .site-header{position:sticky;top:0;z-index:30;background:rgba(255,255,255,.92);backdrop-filter:blur(10px);border-bottom:1px solid rgba(0,0,0,.05)}
    .site-header-inner{display:flex;align-items:center;justify-content:space-between;gap:16px;min-height:72px}
    .brand{font-weight:800;font-size:20px;text-decoration:none}
    .menu{display:flex;flex-wrap:wrap;gap:14px}
    .menu a{text-decoration:none;color:var(--muted);font-weight:700}
    .menu a:hover{color:var(--text)}
    .page-shell{padding:32px 0 48px}
    .page-surface{background:var(--surface);border-radius:28px;box-shadow:0 30px 60px -35px rgba(0,0,0,.22);overflow:hidden}
    .page-content{padding:0}
    .site-footer{padding:22px 0 42px;color:var(--muted);font-size:14px;text-align:center}
    @media (max-width:800px){.site-header-inner{display:block;padding:12px 0}.brand{display:block;margin-bottom:10px}.menu{gap:10px}}
  </style>
</head>
<body>
  <header class="site-header">
    <div class="shell site-header-inner">
      <a class="brand" href="/">${site.title || "LinuxCMS"}</a>
      <nav class="menu">${menuHtml}</nav>
    </div>
  </header>
  <main class="shell page-shell">
    <div class="page-surface">
      <div class="page-content">${htmlContent || "<section style='padding:64px 32px'><p>Página vacía.</p></section>"}</div>
    </div>
  </main>
  <footer class="site-footer">
    <div class="shell">${site.footer_text || ""}</div>
  </footer>
</body>
</html>`;
    }

    const tabs = document.querySelectorAll("[data-tab-target]");
    const panels = document.querySelectorAll("[data-tab-panel]");
    const clientModeToggle = document.getElementById("clientModeToggle");
    const clientModeBannerToggle = document.getElementById("clientModeBannerToggle");
    const clientQuickActions = document.getElementById("clientQuickActions");
    const quickstartGuide = document.querySelector(".quickstart-guide");
    const clientModeStorageKey = "ccms-client-mode";
    tabs.forEach((tabButton) => {
      tabButton.addEventListener("click", () => {
        const target = tabButton.dataset.tabTarget;
        tabs.forEach((button) => button.classList.toggle("active", button === tabButton));
        panels.forEach((panel) => panel.classList.toggle("active", panel.dataset.tabPanel === target));
      });
    });

    function activateEditorTab(target) {
      const button = Array.from(tabs).find((item) => item.dataset.tabTarget === target && item.offsetParent !== null);
      button?.click();
    }

    function setClientMode(enabled) {
      document.body.classList.toggle("client-mode", enabled);
      if (clientModeToggle) {
        clientModeToggle.textContent = enabled ? "Modo avanzado" : "Modo cliente";
        clientModeToggle.setAttribute("aria-pressed", enabled ? "true" : "false");
      }
      if (clientModeBannerToggle) {
        clientModeBannerToggle.textContent = enabled ? "Cambiar a modo avanzado" : "Volver a modo cliente";
      }
      try {
        window.localStorage.setItem(clientModeStorageKey, enabled ? "1" : "0");
      } catch (error) {
        console.warn("Could not persist client mode:", error);
      }
      const activeHiddenTab = Array.from(tabs).find((button) => button.classList.contains("active") && button.offsetParent === null);
      if (enabled && activeHiddenTab) {
        activateEditorTab("content");
      }
    }

    function getInitialClientMode() {
      try {
        const stored = window.localStorage.getItem(clientModeStorageKey);
        if (stored === "0" || stored === "1") {
          return stored === "1";
        }
      } catch (error) {
        console.warn("Could not read client mode:", error);
      }
      return true;
    }

    clientModeToggle?.addEventListener("click", () => {
      setClientMode(!document.body.classList.contains("client-mode"));
    });

    clientModeBannerToggle?.addEventListener("click", () => {
      setClientMode(!document.body.classList.contains("client-mode"));
    });

    clientQuickActions?.addEventListener("click", (event) => {
      const button = event.target.closest("[data-client-focus]");
      if (!button) return;
      const target = button.dataset.clientFocus || "";
      if (target === "site") {
        window.location.href = "/r-admin/?tab=site";
        return;
      }
      if (target) {
        activateEditorTab(target);
      }
    });

    function focusPreviewPanel() {
      const previewCard = preview?.closest(".editor-card");
      previewCard?.scrollIntoView({ block: "start", behavior: "smooth" });
      preview?.focus?.({ preventScroll: true });
    }

    quickstartGuide?.addEventListener("click", (event) => {
      const button = event.target.closest("[data-guide-target]");
      if (!button) return;
      const target = button.dataset.guideTarget || "";
      if (target === "preview-text" || target === "preview-media") {
        activateEditorTab("content");
        window.setTimeout(() => focusPreviewPanel(), 80);
        return;
      }
      if (target === "builder" || target === "publish") {
        activateEditorTab(target);
        return;
      }
      if (target === "site") {
        window.location.href = "/r-admin/?tab=site";
      }
    });

    const htmlEditor = document.getElementById("html_content");
    const preview = document.getElementById("pagePreview");
    const pageTitle = document.getElementById("page_title");
    const capsuleTextarea = document.querySelector('textarea[name="capsule_json"]');
    const pageEditorForm = document.getElementById("pageEditorForm");
    const postEditorForm = document.getElementById("postEditorForm");
    const siteSettingsForm = document.getElementById("siteSettingsForm");
    const builderList = document.getElementById("builderList");
    const builderTemplateGrid = document.getElementById("builderTemplateGrid");
    const builderInsertHint = document.getElementById("builderInsertHint");
    const builderBlockCount = document.getElementById("builderBlockCount");
    const builderSyncButton = document.getElementById("builderSyncJson");
    const builderContext = document.getElementById("builderContext");
    const builderGlobalStyle = document.getElementById("builderGlobalStyle");
    const previewEndpoint = "/r-admin/preview.php";
    const previewShell = document.getElementById("previewShell");
    const autosaveStatus = document.getElementById("autosaveStatus");
    const autosaveMeta = document.getElementById("autosaveMeta");
    const pageAutosaveFlag = document.getElementById("pageAutosaveFlag");
    let previewTimer = null;
    let autosaveTimer = null;
    let autosaveInFlight = false;
    let autosaveQueued = false;
    let autosaveDirty = false;
    let lastAutosaveAt = "";

    const capsuleGlobalStyleFields = [
      { key: "accent", label: "Accent", type: "color", fallback: "#c86f5c" },
      { key: "accent_dark", label: "Accent dark", type: "color", fallback: "#ab5d4e" },
      { key: "bg_from", label: "Background from", type: "color", fallback: "#f7f4ee" },
      { key: "bg_to", label: "Background to", type: "color", fallback: "#ffffff" },
      { key: "card_bg", label: "Card background", type: "text", fallback: "rgba(255,255,255,0.96)" },
      { key: "card_border", label: "Card border", type: "text", fallback: "rgba(0,0,0,0.08)" },
      { key: "gradient_accent", label: "Gradient accent", type: "text", fallback: "linear-gradient(135deg,#c86f5c 0%,#d9c4b3 100%)" },
      { key: "text_primary", label: "Text primary", type: "color", fallback: "#2f241f" },
      { key: "text_secondary", label: "Text secondary", type: "color", fallback: "#6b5b53" },
      { key: "text_muted", label: "Text muted", type: "color", fallback: "#7c6a60" },
      { key: "nav_bg", label: "Navigation background", type: "text", fallback: "rgba(255,255,255,0.92)" },
      { key: "font_family", label: "Body font", type: "text", fallback: "Inter, Arial, Helvetica, sans-serif" },
      { key: "font_heading", label: "Heading font", type: "text", fallback: "Inter, Arial, Helvetica, sans-serif" },
    ];

    const blockStyleFields = [
      { key: "padding_top", label: "Padding top", type: "number", placeholder: "Default" },
      { key: "padding_bottom", label: "Padding bottom", type: "number", placeholder: "Default" },
      { key: "content_width", label: "Content width", type: "number", placeholder: "Default" },
      { key: "text_align", label: "Text align", type: "select", options: ["", "left", "center", "right"] },
      { key: "background", label: "Background override", type: "text", placeholder: "#fff or linear-gradient(...)" },
      { key: "text_color", label: "Text color", type: "color", placeholder: "Optional" },
      { key: "button_bg", label: "Button background", type: "color", placeholder: "Optional" },
      { key: "button_text_color", label: "Button text color", type: "color", placeholder: "Optional" },
      { key: "button_border_color", label: "Button border color", type: "color", placeholder: "Optional" },
      { key: "button_ghost_bg", label: "Ghost button background", type: "color", placeholder: "Optional" },
      { key: "button_ghost_text_color", label: "Ghost button text color", type: "color", placeholder: "Optional" },
      { key: "button_ghost_border_color", label: "Ghost button border color", type: "color", placeholder: "Optional" },
    ];

    function escapeHtml(value) {
      return String(value ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
    }

    function deepClone(value) {
      return JSON.parse(JSON.stringify(value));
    }

    function isPlainObject(value) {
      return !!value && typeof value === "object" && !Array.isArray(value);
    }

    function isScalarValue(value) {
      return ["string", "number", "boolean"].includes(typeof value) || value === null;
    }

    function isSimpleScalarArray(value) {
      return Array.isArray(value) && value.every((item) => isScalarValue(item));
    }

    function isSimpleObjectArray(value) {
      return Array.isArray(value) && value.every((item) => {
        if (!isPlainObject(item)) return false;
        return Object.values(item).every((nested) => isScalarValue(nested) || isSimpleScalarArray(nested));
      });
    }

    function createDefaultScalarItem(parentKey) {
      const key = String(parentKey || "").toLowerCase();
      if (key.includes("feature")) return "Feature";
      if (key.includes("bullet")) return "New bullet";
      if (key.includes("line")) return "New line";
      if (key.includes("tag")) return "New tag";
      return "New item";
    }

    function createDefaultObjectItem(parentKey) {
      const key = String(parentKey || "").toLowerCase();
      if (key.includes("link")) return { text: "New link", href: "#" };
      if (key.includes("image")) return { url: "", alt: "New image" };
      if (key.includes("plan")) return { name: "New plan", price: "$0", features: ["Feature"], cta: "Start", highlighted: false };
      if (key.includes("post")) return { category: "Article", title: "New post", excerpt: "Short description", href: "#" };
      if (key.includes("project")) return { category: "Project", title: "New project", metric: "New metric", href: "#" };
      if (key.includes("service")) return { title: "New service", desc: "Short description", bullets: ["Point"], cta_text: "Learn more", cta_href: "#" };
      if (key.includes("column")) return { title: "Column", links: [{ text: "Link", href: "#" }] };
      if (key.includes("item")) return { title: "New item", desc: "Short description" };
      return { title: "New item", text: "Edit this content" };
    }

    function dataAttributes(attributes) {
      return Object.entries(attributes).map(([key, value]) => `data-${escapeHtml(key)}="${escapeHtml(value)}"`).join(" ");
    }

    function isImageLikeKey(key, parentKey = "") {
      const currentKey = String(key || "").toLowerCase();
      const parent = String(parentKey || "").toLowerCase();
      if (/(image|photo|avatar|logo|thumbnail|background|banner|cover|poster|mockup|favicon)/.test(currentKey)) return true;
      if ((currentKey === "url" || currentKey === "src") && /(image|photo|avatar|logo|thumbnail|gallery|banner|hero|project|post|portfolio)/.test(parent)) return true;
      return false;
    }

    function isLinkLikeKey(key, parentKey = "") {
      const haystack = `${parentKey} ${key}`.toLowerCase();
      if (isImageLikeKey(key, parentKey)) return false;
      return /href|url|link|cta_href|button_href|button_url|profile_url|instagram|youtube|facebook|linkedin|tiktok|whatsapp|telegram/.test(haystack);
    }

    function renderMediaPicker(scope, attributes) {
      if (!Array.isArray(mediaItems) || !mediaItems.length) {
        return `<div class="builder-media-picker"><div class="builder-media-empty">Todavía no hay archivos en la biblioteca media. Sube imágenes en la pestaña <strong>Media</strong> y volverán a aparecer aquí.</div></div>`;
      }
      const safeAttrs = dataAttributes(attributes);
      return `
        <details class="builder-media-picker">
          <summary>Elegir desde la biblioteca media</summary>
          <div class="builder-media-picker-grid">
            ${mediaItems.slice(0, 10).map((asset) => `
              <button class="builder-media-option" type="button" data-builder-pick-media="${escapeHtml(scope)}" data-media-url="${escapeHtml(asset.url || "")}" ${safeAttrs}>
                <img src="${escapeHtml(asset.url || "")}" alt="${escapeHtml(asset.name || "Media")}">
                <span>${escapeHtml(asset.name || asset.url || "Imagen")}</span>
              </button>
            `).join("")}
          </div>
        </details>
      `;
    }

    function createBlockId() {
      const randomPart = Math.random().toString(16).slice(2, 10);
      return "block_" + randomPart;
    }

    function normalizeSearchText(value) {
      return String(value || "")
        .toLowerCase()
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .trim();
    }

    function normalizeCapsule(input) {
      const capsule = (input && typeof input === "object") ? deepClone(input) : {};
      if (!capsule.meta || typeof capsule.meta !== "object") capsule.meta = {};
      if (!capsule.style || typeof capsule.style !== "object") capsule.style = {};
      if (!Array.isArray(capsule.blocks)) capsule.blocks = [];
      capsule.blocks = capsule.blocks.map((block, index) => ({
        id: block && block.id ? String(block.id) : createBlockId(),
        type: block && block.type ? String(block.type) : "text_block",
        props: block && typeof block.props === "object" && block.props ? block.props : {},
        style: block && typeof block.style === "object" && block.style ? block.style : {},
        _order: index,
      }));
      return capsule;
    }

    let capsuleState = normalizeCapsule(initialCapsuleState);
    let builderDragState = null;
    let activeBuilderBlockIndex = capsuleState.blocks.length ? 0 : -1;
    let pendingInsertIndex = capsuleState.blocks.length;
    let mediaModalState = null;

    function isLongTextField(key, value) {
      return ["subtitle", "text", "quote", "description", "privacy_text", "info", "copyright"].includes(key)
        || (typeof value === "string" && value.length > 80);
    }

    function syncCapsuleTextarea() {
      if (!capsuleTextarea) return;
      const capsuleToSave = {
        meta: capsuleState.meta || {},
        style: capsuleState.style || {},
        blocks: (capsuleState.blocks || []).map(({ _order, ...block }) => block),
      };
      capsuleTextarea.value = JSON.stringify(capsuleToSave, null, 2);
      if (builderBlockCount) {
        const count = Array.isArray(capsuleToSave.blocks) ? capsuleToSave.blocks.length : 0;
        builderBlockCount.textContent = `${count} bloque${count === 1 ? "" : "s"}`;
      }
      schedulePreviewRefresh();
    }

    function highlightPreviewBlock(index) {
      if (!preview || !preview.contentWindow || index < 0) return;
      try {
        preview.contentWindow.postMessage({ type: "ccms-parent-highlight-block", index }, "*");
      } catch (error) {
        console.warn("Preview highlight failed:", error);
      }
    }

    function selectBuilderBlock(index, options = {}) {
      const { scroll = true, syncPreview = true } = options;
      if (!Array.isArray(capsuleState.blocks) || !capsuleState.blocks.length) {
        activeBuilderBlockIndex = -1;
        pendingInsertIndex = 0;
        renderBuilderBlocks();
        return;
      }
      const normalizedIndex = Math.max(0, Math.min(index, capsuleState.blocks.length - 1));
      activeBuilderBlockIndex = normalizedIndex;
      pendingInsertIndex = normalizeInsertIndex(normalizedIndex + 1);
      renderBuilderBlocks();
      renderBuilderContext();
      const selected = builderList?.querySelector(`[data-builder-block="${normalizedIndex}"]`);
      if (scroll && selected) {
        selected.scrollIntoView({ block: "nearest", behavior: "smooth" });
      }
      if (syncPreview) {
        highlightPreviewBlock(normalizedIndex);
      }
    }

    function schedulePreviewRefresh(delay = 220) {
      if (!preview) return;
      window.clearTimeout(previewTimer);
      previewTimer = window.setTimeout(() => {
        refreshPreview();
      }, delay);
    }

    function setPreviewLoading(loading) {
      if (!previewShell) return;
      previewShell.classList.toggle("is-loading", !!loading);
    }

    function formatAutosaveTimestamp(value) {
      if (!value) return "";
      const date = new Date(value);
      if (Number.isNaN(date.getTime())) return "";
      return date.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
    }

    function setAutosaveState(state, meta = "") {
      if (!autosaveStatus) return;
      const labels = {
        saved: { icon: "circle-check", text: "Guardado" },
        dirty: { icon: "circle-dashed", text: "Pendiente" },
        saving: { icon: "circle-dot", text: "Guardando" },
        error: { icon: "alert-triangle", text: "Error" },
      };
      const label = labels[state] || labels.saved;
      autosaveStatus.className = `autosave-pill is-${state}`;
      autosaveStatus.innerHTML = `${window.ccmsIcon ? window.ccmsIcon(label.icon, 14) : ""}${label.text}`;
      if (autosaveMeta) {
        autosaveMeta.textContent = meta;
      }
    }

    function markAutosaveDirty(message = "Hay cambios sin guardar.") {
      if (!pageEditorForm || builderReadOnly) return;
      autosaveDirty = true;
      setAutosaveState("dirty", message);
      window.clearTimeout(autosaveTimer);
      autosaveTimer = window.setTimeout(() => {
        performAutosave();
      }, 900);
    }

    async function performAutosave() {
      if (!pageEditorForm || builderReadOnly) return;
      if (!autosaveDirty) return;
      if (autosaveInFlight) {
        autosaveQueued = true;
        return;
      }
      autosaveInFlight = true;
      autosaveQueued = false;
      setAutosaveState("saving", "Guardando cambios automáticamente…");
      syncCapsuleTextarea();
      if (pageAutosaveFlag) {
        pageAutosaveFlag.value = "1";
      }
      try {
        const formData = new FormData(pageEditorForm);
        const response = await fetch(pageEditorForm.getAttribute("action") || window.location.pathname + window.location.search, {
          method: "POST",
          body: formData,
          credentials: "same-origin",
          headers: { "X-Requested-With": "XMLHttpRequest" },
        });
        const contentType = response.headers.get("content-type") || "";
        if (!response.ok || !contentType.includes("application/json")) {
          throw new Error(`Autosave failed with ${response.status}`);
        }
        const payload = await response.json();
        if (!payload?.ok) {
          throw new Error("Invalid autosave response");
        }
        autosaveDirty = false;
        lastAutosaveAt = String(payload.updated_at || "");
        const label = formatAutosaveTimestamp(lastAutosaveAt);
        setAutosaveState("saved", label ? `Autoguardado a las ${label}.` : "Todos los cambios están guardados.");
      } catch (error) {
        console.warn("Autosave failed:", error);
        setAutosaveState("error", "No se ha podido autoguardar. Usa Guardar página.");
      } finally {
        if (pageAutosaveFlag) {
          pageAutosaveFlag.value = "0";
        }
        autosaveInFlight = false;
        if (autosaveQueued || autosaveDirty) {
          autosaveQueued = false;
          window.clearTimeout(autosaveTimer);
          autosaveTimer = window.setTimeout(() => {
            performAutosave();
          }, 700);
        }
      }
    }

    function renderBuilderGlobalStyle() {
      if (!builderGlobalStyle) return;
      builderGlobalStyle.innerHTML = capsuleGlobalStyleFields.map((field) => {
        const currentValue = capsuleState.style?.[field.key] ?? field.fallback ?? "";
        if (field.type === "color") {
          return `
            <div class="field">
              <label>${escapeHtml(field.label)}</label>
              <div style="display:grid;grid-template-columns:56px minmax(0,1fr);gap:10px;align-items:center">
                <input type="color" value="${escapeHtml(currentValue)}" data-builder-global-style="${escapeHtml(field.key)}" data-mode="color">
                <input type="text" value="${escapeHtml(currentValue)}" data-builder-global-style="${escapeHtml(field.key)}" data-mode="text">
              </div>
            </div>
          `;
        }
        return `
          <div class="field">
            <label>${escapeHtml(field.label)}</label>
            <input type="text" value="${escapeHtml(currentValue)}" data-builder-global-style="${escapeHtml(field.key)}" data-mode="text" placeholder="${escapeHtml(field.fallback || "")}">
          </div>
        `;
      }).join("");
    }

    function blockDisplayName(block) {
      return (block?.props && (block.props.title || block.props.brand || block.props.badge || block.props.name)) || block?.type || "Bloque";
    }

    function renderBuilderContext() {
      if (!builderContext) return;
      if (!Array.isArray(capsuleState.blocks) || !capsuleState.blocks.length || activeBuilderBlockIndex < 0) {
        builderContext.innerHTML = `
          <div class="builder-context-title">
            <strong>No hay bloque seleccionado</strong>
            <div class="small">Haz clic en un bloque del builder o en una sección dentro de la preview para empezar a editarla.</div>
          </div>
        `;
        return;
      }
      const block = capsuleState.blocks[activeBuilderBlockIndex];
      const insertLabel = pendingInsertIndex <= 0
        ? "al principio"
        : pendingInsertIndex >= capsuleState.blocks.length
          ? "al final"
          : `después del bloque ${pendingInsertIndex}`;
      builderContext.innerHTML = `
        <div class="builder-context-head">
          <div class="builder-context-title">
            <span class="chip">Bloque activo · ${activeBuilderBlockIndex + 1}</span>
            <strong>${escapeHtml(blockDisplayName(block))}</strong>
            <div class="small"><strong>${escapeHtml(block.type || "block")}</strong> · ID ${escapeHtml(block.id || "")}</div>
            <div class="small">La siguiente inserción irá <strong>${escapeHtml(insertLabel)}</strong>.</div>
          </div>
          <div class="builder-context-actions">
            <button class="btn btn-secondary" type="button" data-builder-context="content">Editar contenido</button>
            <button class="btn btn-secondary" type="button" data-builder-context="link">Editar enlace</button>
            <button class="btn btn-secondary" type="button" data-builder-context="media">Editar imágenes</button>
            <button class="btn btn-secondary" type="button" data-builder-context="style">Editar estilo</button>
            <button class="btn btn-secondary" type="button" data-builder-context="insert">Insertar después</button>
            <button class="btn btn-secondary" type="button" data-builder-context="duplicate">Duplicar</button>
            <button class="btn btn-danger" type="button" data-builder-context="remove">Eliminar</button>
          </div>
        </div>
        <div class="small">La preview y el builder están sincronizados. Haz clic en una sección de la preview para saltar directamente a su bloque editable.</div>
      `;
    }

    function focusSelectedBlockField(kind = "content") {
      const blockEl = builderList?.querySelector(`[data-builder-block="${activeBuilderBlockIndex}"]`);
      if (!blockEl) return;
      const selector = kind === "style"
        ? ".builder-subsection [data-builder-style-field]"
        : ".builder-fields [data-builder-field], .builder-fields [data-builder-object-field], .builder-fields [data-builder-scalar-item], .builder-fields textarea, .builder-fields input";
      const target = blockEl.querySelector(selector);
      if (target) {
        target.focus({ preventScroll: false });
        target.scrollIntoView({ block: "center", behavior: "smooth" });
      } else {
        blockEl.scrollIntoView({ block: "nearest", behavior: "smooth" });
      }
    }

    function findSelectedBlockTextField(preferredText = "", tag = "") {
      const blockEl = builderList?.querySelector(`[data-builder-block="${activeBuilderBlockIndex}"]`);
      if (!blockEl) return null;
      const textFields = Array.from(blockEl.querySelectorAll("[data-builder-field][data-mode='string'],[data-builder-object-field][data-mode='string'],[data-builder-nested-object-field][data-mode='string'],[data-builder-scalar-item],[data-builder-nested-scalar]"))
        .filter((field) => {
          const key = field.dataset.key || field.dataset.nestedKey || field.dataset.deepKey || "";
          const parentKey = field.dataset.nestedKey || field.dataset.key || "";
          return !isImageLikeKey(key, parentKey);
        });
      if (!textFields.length) {
        return null;
      }
      const preferred = (preferredText || "").trim().toLowerCase();
      let target = null;
      if (preferred) {
        target = textFields.find((field) => {
          const value = String(field.value || "").trim().toLowerCase();
          return value && (value.includes(preferred) || preferred.includes(value.slice(0, Math.min(value.length, 40))));
        }) || null;
      }
      if (!target) {
        const wantLong = tag === "p" || tag === "li";
        const wantShort = tag === "a" || tag === "button" || /^h[1-6]$/.test(tag);
        target = textFields.find((field) => wantLong && field.tagName === "TEXTAREA")
          || textFields.find((field) => wantShort && field.tagName !== "TEXTAREA")
          || textFields[0];
      }
      return target;
    }

    function focusSelectedBlockTextField(preferredText = "", tag = "") {
      const target = findSelectedBlockTextField(preferredText, tag);
      if (!target) {
        focusSelectedBlockField("content");
        return;
      }
      target.focus({ preventScroll: false });
      if (typeof target.select === "function") {
        target.select();
      }
      target.scrollIntoView({ block: "center", behavior: "smooth" });
    }

    function applySelectedBlockTextField(oldText = "", newText = "", tag = "") {
      const target = findSelectedBlockTextField(oldText, tag);
      if (!target) {
        focusSelectedBlockField("content");
        return false;
      }
      target.value = newText;
      target.dispatchEvent(new Event("input", { bubbles: true }));
      target.focus({ preventScroll: false });
      if (typeof target.select === "function") {
        target.select();
      }
      target.scrollIntoView({ block: "center", behavior: "smooth" });
      return true;
    }

    function findSelectedBlockMediaField(preferredSrc = "") {
      const blockEl = builderList?.querySelector(`[data-builder-block="${activeBuilderBlockIndex}"]`);
      if (!blockEl) return null;
      const candidates = Array.from(blockEl.querySelectorAll("[data-builder-field],[data-builder-object-field],[data-builder-nested-object-field]"));
      const preferred = (preferredSrc || "").trim();
      let target = null;
      if (preferred) {
        target = candidates.find((field) => String(field.value || "").trim() === preferred) || null;
      }
      if (!target) {
        target = candidates.find((field) => {
        const key = field.dataset.key || field.dataset.nestedKey || field.dataset.deepKey || "";
        const parentKey = field.dataset.nestedKey || field.dataset.key || "";
        return isImageLikeKey(key, parentKey);
        }) || null;
      }
      return target;
    }

    function focusSelectedBlockMediaField(preferredSrc = "") {
      const blockEl = builderList?.querySelector(`[data-builder-block="${activeBuilderBlockIndex}"]`);
      if (!blockEl) return;
      const target = findSelectedBlockMediaField(preferredSrc);
      if (target) {
        target.focus({ preventScroll: false });
        if (typeof target.select === "function") {
          target.select();
        }
        target.scrollIntoView({ block: "center", behavior: "smooth" });
        return;
      }
      const mediaButton = blockEl.querySelector("[data-builder-pick-media]");
      if (mediaButton) {
        mediaButton.scrollIntoView({ block: "center", behavior: "smooth" });
      } else {
        blockEl.scrollIntoView({ block: "nearest", behavior: "smooth" });
      }
    }

    function focusSelectedBlockLinkField(preferredHref = "", preferredText = "") {
      const blockEl = builderList?.querySelector(`[data-builder-block="${activeBuilderBlockIndex}"]`);
      if (!blockEl) return;
      const candidates = Array.from(blockEl.querySelectorAll("[data-builder-field][data-mode='string'],[data-builder-object-field][data-mode='string'],[data-builder-nested-object-field][data-mode='string']"));
      const preferred = (preferredHref || "").trim();
      const preferredLabel = (preferredText || "").trim().toLowerCase();
      let target = null;
      if (preferred) {
        target = candidates.find((field) => String(field.value || "").trim() === preferred) || null;
      }
      if (!target && preferredLabel) {
        target = candidates.find((field) => {
          const wrapper = field.closest(".builder-field, .builder-object-field, .builder-nested-object-field");
          const label = String(wrapper?.querySelector("label, strong")?.textContent || "").trim().toLowerCase();
          return label && (label.includes(preferredLabel) || preferredLabel.includes(label));
        }) || null;
      }
      if (!target) {
        target = candidates.find((field) => {
          const key = field.dataset.deepKey || field.dataset.nestedKey || field.dataset.key || "";
          const parentKey = field.dataset.nestedKey || field.dataset.key || "";
          return isLinkLikeKey(key, parentKey);
        }) || null;
      }
      if (target) {
        target.focus({ preventScroll: false });
        if (typeof target.select === "function") target.select();
        target.scrollIntoView({ block: "center", behavior: "smooth" });
        return;
      }
      focusSelectedBlockField("content");
    }

    function ensureMediaModal() {
      let backdrop = document.getElementById("media-modal-backdrop");
      if (backdrop) return backdrop;
      backdrop = document.createElement("div");
      backdrop.id = "media-modal-backdrop";
      backdrop.className = "media-modal-backdrop";
      backdrop.innerHTML = `
        <div class="media-modal" role="dialog" aria-modal="true" aria-labelledby="media-modal-title">
          <div class="media-modal-head">
            <div>
              <h3 id="media-modal-title">Seleccionar imagen</h3>
              <p>Elige una imagen de la biblioteca para aplicarla directamente al bloque seleccionado.</p>
            </div>
            <button class="btn btn-secondary" type="button" data-media-modal-close>Cerrar</button>
          </div>
          <div class="media-modal-toolbar">
            <input type="search" id="media-modal-search" placeholder="Buscar por nombre o URL">
            <button class="btn btn-secondary" type="button" data-media-modal-fallback>Ir al campo del builder</button>
          </div>
          <div class="media-modal-grid" id="media-modal-grid"></div>
          <div class="media-modal-footer">
            <div class="small" id="media-modal-status">Elige una imagen o pega una URL directamente en el builder.</div>
            <button class="btn btn-secondary" type="button" data-media-modal-close>Cerrar</button>
          </div>
        </div>
      `;
      document.body.appendChild(backdrop);
      backdrop.addEventListener("click", (event) => {
        if (event.target === backdrop || event.target.closest("[data-media-modal-close]")) {
          closeMediaModal();
        }
      });
      backdrop.querySelector("#media-modal-search")?.addEventListener("input", () => {
        renderMediaModalGrid();
      });
      backdrop.querySelector("[data-media-modal-fallback]")?.addEventListener("click", () => {
        if (mediaModalState?.preferredSrc) {
          focusSelectedBlockMediaField(mediaModalState.preferredSrc);
        } else {
          focusSelectedBlockMediaField();
        }
        closeMediaModal();
      });
      window.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && backdrop.classList.contains("is-open")) {
          closeMediaModal();
        }
      });
      return backdrop;
    }

    function renderMediaModalGrid() {
      const backdrop = ensureMediaModal();
      const grid = backdrop.querySelector("#media-modal-grid");
      const status = backdrop.querySelector("#media-modal-status");
      const search = String(backdrop.querySelector("#media-modal-search")?.value || "").trim().toLowerCase();
      if (!grid) return;
      if (!Array.isArray(mediaItems) || !mediaItems.length) {
        grid.innerHTML = `<div class="media-modal-empty">Todavía no hay imágenes en la biblioteca. Sube imágenes en la pestaña <strong>Media</strong> y vuelve a intentarlo.</div>`;
        if (status) status.textContent = "No hay archivos disponibles en la biblioteca media.";
        return;
      }
      const items = mediaItems.filter((asset) => {
        if (!search) return true;
        const haystack = `${asset.name || ""} ${asset.original_name || ""} ${asset.url || ""}`.toLowerCase();
        return haystack.includes(search);
      });
      if (!items.length) {
        grid.innerHTML = `<div class="media-modal-empty">No hay resultados para esa búsqueda.</div>`;
        if (status) status.textContent = "No se han encontrado imágenes con ese criterio.";
        return;
      }
      grid.innerHTML = items.map((asset) => `
        <button class="media-modal-card" type="button" data-media-modal-select="${escapeHtml(asset.url || "")}">
          <img src="${escapeHtml(asset.url || "")}" alt="${escapeHtml(asset.name || "Media")}">
          <span>${escapeHtml(asset.name || asset.original_name || asset.url || "Imagen")}</span>
        </button>
      `).join("");
      grid.querySelectorAll("[data-media-modal-select]").forEach((button) => {
        button.addEventListener("click", () => {
          if (!mediaModalState?.target) {
            focusSelectedBlockMediaField(mediaModalState?.preferredSrc || "");
            closeMediaModal();
            return;
          }
          mediaModalState.target.value = button.dataset.mediaModalSelect || "";
          mediaModalState.target.dispatchEvent(new Event("input", { bubbles: true }));
          mediaModalState.target.focus({ preventScroll: false });
          mediaModalState.target.scrollIntoView({ block: "center", behavior: "smooth" });
          if (status) status.textContent = "Imagen aplicada al bloque seleccionado.";
          closeMediaModal();
        });
      });
      if (status) status.textContent = `${items.length} imagen${items.length === 1 ? "" : "es"} disponible${items.length === 1 ? "" : "s"} para aplicar.`;
    }

    function openPreviewMediaPicker(preferredSrc = "") {
      const target = findSelectedBlockMediaField(preferredSrc);
      if (!target) {
        focusSelectedBlockMediaField(preferredSrc);
        return;
      }
      const backdrop = ensureMediaModal();
      mediaModalState = {
        target,
        preferredSrc: String(preferredSrc || "").trim(),
      };
      backdrop.classList.add("is-open");
      const search = backdrop.querySelector("#media-modal-search");
      if (search) search.value = "";
      renderMediaModalGrid();
      search?.focus({ preventScroll: true });
    }

    function closeMediaModal() {
      const backdrop = document.getElementById("media-modal-backdrop");
      if (!backdrop) return;
      backdrop.classList.remove("is-open");
      mediaModalState = null;
    }

    function applySelectedBlockLinkField(oldHref = "", newHref = "", preferredText = "") {
      const blockEl = builderList?.querySelector(`[data-builder-block="${activeBuilderBlockIndex}"]`);
      if (!blockEl) return false;
      const candidates = Array.from(blockEl.querySelectorAll("[data-builder-field][data-mode='string'],[data-builder-object-field][data-mode='string'],[data-builder-nested-object-field][data-mode='string']"));
      const previousHref = (oldHref || "").trim();
      const nextHref = (newHref || "").trim();
      const preferredLabel = (preferredText || "").trim().toLowerCase();
      let target = null;
      if (previousHref) {
        target = candidates.find((field) => String(field.value || "").trim() === previousHref) || null;
      }
      if (!target && preferredLabel) {
        target = candidates.find((field) => {
          const wrapper = field.closest(".builder-field, .builder-object-field, .builder-nested-object-field");
          const label = String(wrapper?.querySelector("label, strong")?.textContent || "").trim().toLowerCase();
          return label && (label.includes(preferredLabel) || preferredLabel.includes(label));
        }) || null;
      }
      if (!target) {
        target = candidates.find((field) => {
          const key = field.dataset.deepKey || field.dataset.nestedKey || field.dataset.key || "";
          const parentKey = field.dataset.nestedKey || field.dataset.key || "";
          return isLinkLikeKey(key, parentKey);
        }) || null;
      }
      if (!target) {
        focusSelectedBlockLinkField(previousHref, preferredText);
        return false;
      }
      target.value = nextHref;
      target.dispatchEvent(new Event("input", { bubbles: true }));
      target.focus({ preventScroll: false });
      if (typeof target.select === "function") target.select();
      target.scrollIntoView({ block: "center", behavior: "smooth" });
      return true;
    }

    function applySelectedBlockButtonStyle(styleUpdates = {}) {
      if (activeBuilderBlockIndex < 0 || !capsuleState.blocks[activeBuilderBlockIndex]) return false;
      capsuleState.blocks[activeBuilderBlockIndex].style ||= {};
      Object.entries(styleUpdates || {}).forEach(([key, value]) => {
        const normalized = String(value || "").trim();
        if (normalized === "") {
          delete capsuleState.blocks[activeBuilderBlockIndex].style[key];
        } else {
          capsuleState.blocks[activeBuilderBlockIndex].style[key] = normalized;
        }
      });
      if (Object.prototype.hasOwnProperty.call(styleUpdates || {}, "button_variant")) {
        const variantValue = String(styleUpdates.button_variant || "").trim();
        if (variantValue === "") {
          delete capsuleState.blocks[activeBuilderBlockIndex].style.button_variant;
        } else {
          capsuleState.blocks[activeBuilderBlockIndex].style.button_variant = variantValue;
        }
      }
      markAutosaveDirty("Se actualizó el estilo del botón.");
      renderBuilderBlocks();
      selectBuilderBlock(activeBuilderBlockIndex, { scroll: false, syncPreview: true });
      return true;
    }

    function renderBlockStyleField(index, field, value) {
      const safeKey = escapeHtml(field.key);
      const safeLabel = escapeHtml(field.label);
      if (field.type === "select") {
        const options = (field.options || []).map((option) => {
          const optionLabel = option === "" ? "Default" : option;
          return `<option value="${escapeHtml(option)}" ${value === option ? "selected" : ""}>${escapeHtml(optionLabel)}</option>`;
        }).join("");
        return `
          <div class="field">
            <label>${safeLabel}</label>
            <select data-builder-style-field="${index}" data-key="${safeKey}" data-mode="select">${options}</select>
          </div>
        `;
      }
      if (field.type === "color") {
        const colorValue = value || "#000000";
        return `
          <div class="field">
            <label>${safeLabel}</label>
            <div style="display:grid;grid-template-columns:56px minmax(0,1fr);gap:10px;align-items:center">
              <input type="color" value="${escapeHtml(colorValue)}" data-builder-style-field="${index}" data-key="${safeKey}" data-mode="color">
              <input type="text" value="${escapeHtml(value || "")}" data-builder-style-field="${index}" data-key="${safeKey}" data-mode="text" placeholder="${escapeHtml(field.placeholder || "")}">
            </div>
          </div>
        `;
      }
      return `
        <div class="field">
          <label>${safeLabel}</label>
          <input type="${field.type === "number" ? "number" : "text"}" value="${escapeHtml(value ?? "")}" data-builder-style-field="${index}" data-key="${safeKey}" data-mode="${escapeHtml(field.type)}" placeholder="${escapeHtml(field.placeholder || "")}">
        </div>
      `;
    }

    function renderNestedScalarList(blockIndex, key, parentIndex, nestedKey, values) {
      const items = values.length ? values : [createDefaultScalarItem(nestedKey)];
      return `
        <div class="builder-full" data-parent-item-index="${parentIndex}">
          <label>${escapeHtml(nestedKey)}</label>
          <div class="builder-inline-list">
            ${items.map((item, nestedIndex) => `
              <div class="builder-inline-item">
                <input value="${escapeHtml(item ?? "")}" data-builder-nested-scalar="${blockIndex}" data-key="${escapeHtml(key)}" data-parent-index="${parentIndex}" data-nested-key="${escapeHtml(nestedKey)}" data-item-index="${nestedIndex}">
                <div class="builder-inline-actions">
                  <button class="btn btn-secondary" type="button" data-builder-nested-action="nested-scalar-up" data-index="${blockIndex}" data-key="${escapeHtml(key)}" data-parent-index="${parentIndex}" data-nested-key="${escapeHtml(nestedKey)}" data-item-index="${nestedIndex}">↑</button>
                  <button class="btn btn-secondary" type="button" data-builder-nested-action="nested-scalar-down" data-index="${blockIndex}" data-key="${escapeHtml(key)}" data-parent-index="${parentIndex}" data-nested-key="${escapeHtml(nestedKey)}" data-item-index="${nestedIndex}">↓</button>
                  <button class="btn btn-danger" type="button" data-builder-nested-action="nested-scalar-remove" data-index="${blockIndex}" data-key="${escapeHtml(key)}" data-parent-index="${parentIndex}" data-nested-key="${escapeHtml(nestedKey)}" data-item-index="${nestedIndex}">Eliminar</button>
                </div>
              </div>
            `).join("")}
            <button class="btn btn-secondary" type="button" data-builder-nested-action="nested-scalar-add" data-index="${blockIndex}" data-key="${escapeHtml(key)}" data-parent-index="${parentIndex}" data-nested-key="${escapeHtml(nestedKey)}">Añadir ${escapeHtml(nestedKey)}</button>
          </div>
        </div>
      `;
    }

    function renderNestedObjectArray(blockIndex, key, parentIndex, nestedKey, values) {
      const items = values.length ? values : [createDefaultObjectItem(nestedKey)];
      return `
        <div class="builder-full" data-parent-item-index="${parentIndex}">
          <label>${escapeHtml(nestedKey)}</label>
          <div class="builder-repeater-list">
            ${items.map((item, nestedIndex) => {
              const title = item.title || item.text || item.label || item.name || `Item ${nestedIndex + 1}`;
              const fields = Object.entries(item).map(([deepKey, deepValue]) => {
                if (typeof deepValue === "boolean") {
                  return `
                    <label class="check builder-full">
                      <input type="checkbox" data-builder-nested-object-field="${blockIndex}" data-key="${escapeHtml(key)}" data-parent-index="${parentIndex}" data-nested-key="${escapeHtml(nestedKey)}" data-item-index="${nestedIndex}" data-deep-key="${escapeHtml(deepKey)}" data-mode="boolean" ${deepValue ? "checked" : ""}>
                      ${escapeHtml(deepKey)}
                    </label>
                  `;
                }
                if (typeof deepValue === "number") {
                  return `
                    <div class="field">
                      <label>${escapeHtml(deepKey)}</label>
                      <input type="number" value="${escapeHtml(deepValue)}" data-builder-nested-object-field="${blockIndex}" data-key="${escapeHtml(key)}" data-parent-index="${parentIndex}" data-nested-key="${escapeHtml(nestedKey)}" data-item-index="${nestedIndex}" data-deep-key="${escapeHtml(deepKey)}" data-mode="number">
                    </div>
                  `;
                }
                if (isScalarValue(deepValue)) {
                  const longField = isLongTextField(deepKey, deepValue);
                  const imageField = typeof deepValue === "string" && isImageLikeKey(deepKey, nestedKey);
                  if (longField) {
                    return `
                      <div class="field builder-full">
                        <label>${escapeHtml(deepKey)}</label>
                        <textarea data-builder-nested-object-field="${blockIndex}" data-key="${escapeHtml(key)}" data-parent-index="${parentIndex}" data-nested-key="${escapeHtml(nestedKey)}" data-item-index="${nestedIndex}" data-deep-key="${escapeHtml(deepKey)}" data-mode="string">${escapeHtml(deepValue ?? "")}</textarea>
                        ${imageField ? renderMediaPicker("nested-object", {
                          "target-block": blockIndex,
                          "target-key": key,
                          "target-parent-index": parentIndex,
                          "target-nested-key": nestedKey,
                          "target-item-index": nestedIndex,
                          "target-deep-key": deepKey,
                        }) : ""}
                      </div>
                    `;
                  }
                  if (imageField) {
                    return `
                      <div class="field builder-full">
                        <label>${escapeHtml(deepKey)}</label>
                        <input value="${escapeHtml(deepValue ?? "")}" data-builder-nested-object-field="${blockIndex}" data-key="${escapeHtml(key)}" data-parent-index="${parentIndex}" data-nested-key="${escapeHtml(nestedKey)}" data-item-index="${nestedIndex}" data-deep-key="${escapeHtml(deepKey)}" data-mode="string">
                        ${renderMediaPicker("nested-object", {
                          "target-block": blockIndex,
                          "target-key": key,
                          "target-parent-index": parentIndex,
                          "target-nested-key": nestedKey,
                          "target-item-index": nestedIndex,
                          "target-deep-key": deepKey,
                        })}
                      </div>
                    `;
                  }
                  return `
                    <div class="field">
                      <label>${escapeHtml(deepKey)}</label>
                      <input value="${escapeHtml(deepValue ?? "")}" data-builder-nested-object-field="${blockIndex}" data-key="${escapeHtml(key)}" data-parent-index="${parentIndex}" data-nested-key="${escapeHtml(nestedKey)}" data-item-index="${nestedIndex}" data-deep-key="${escapeHtml(deepKey)}" data-mode="string">
                    </div>
                  `;
                }
                return `
                  <div class="field builder-full">
                    <label>${escapeHtml(deepKey)} (JSON)</label>
                    <textarea class="builder-json" data-builder-nested-object-json="${blockIndex}" data-key="${escapeHtml(key)}" data-parent-index="${parentIndex}" data-nested-key="${escapeHtml(nestedKey)}" data-item-index="${nestedIndex}" data-deep-key="${escapeHtml(deepKey)}">${escapeHtml(JSON.stringify(deepValue, null, 2))}</textarea>
                  </div>
                `;
              }).join("");

              return `
                <article class="builder-repeater-card" draggable="true" data-builder-drag-scope="nested-object" data-builder-drag-block="${blockIndex}" data-builder-drag-key="${escapeHtml(key)}" data-builder-drag-parent-index="${parentIndex}" data-builder-drag-nested-key="${escapeHtml(nestedKey)}" data-builder-drag-item-index="${nestedIndex}">
                  <div class="builder-repeater-card-header">
                    <div class="builder-repeater-card-title">
                      <span class="chip">${nestedIndex + 1}</span>
                      <strong>${escapeHtml(title)}</strong>
                    </div>
                    <div class="builder-repeater-card-actions">
                      <button class="btn btn-secondary" type="button" data-builder-nested-action="nested-object-up" data-index="${blockIndex}" data-key="${escapeHtml(key)}" data-parent-index="${parentIndex}" data-nested-key="${escapeHtml(nestedKey)}" data-item-index="${nestedIndex}">Subir</button>
                      <button class="btn btn-secondary" type="button" data-builder-nested-action="nested-object-down" data-index="${blockIndex}" data-key="${escapeHtml(key)}" data-parent-index="${parentIndex}" data-nested-key="${escapeHtml(nestedKey)}" data-item-index="${nestedIndex}">Bajar</button>
                      <button class="btn btn-secondary" type="button" data-builder-nested-action="nested-object-duplicate" data-index="${blockIndex}" data-key="${escapeHtml(key)}" data-parent-index="${parentIndex}" data-nested-key="${escapeHtml(nestedKey)}" data-item-index="${nestedIndex}">Duplicar</button>
                      <button class="btn btn-danger" type="button" data-builder-nested-action="nested-object-remove" data-index="${blockIndex}" data-key="${escapeHtml(key)}" data-parent-index="${parentIndex}" data-nested-key="${escapeHtml(nestedKey)}" data-item-index="${nestedIndex}">Eliminar</button>
                    </div>
                  </div>
                  <div class="builder-repeater-fields">
                    ${fields}
                  </div>
                </article>
              `;
            }).join("")}
            <button class="btn btn-secondary" type="button" data-builder-nested-action="nested-object-add" data-index="${blockIndex}" data-key="${escapeHtml(key)}" data-parent-index="${parentIndex}" data-nested-key="${escapeHtml(nestedKey)}">Añadir ${escapeHtml(nestedKey)}</button>
          </div>
        </div>
      `;
    }

    function renderRepeaterArray(blockIndex, key, values) {
      const list = Array.isArray(values) ? values : [];
      const isScalarArray = isSimpleScalarArray(list);
      const isObjectArray = isSimpleObjectArray(list);

      if (!isScalarArray && !isObjectArray) {
        return `
          <div class="field builder-full">
            <label>${escapeHtml(key)} (JSON)</label>
            <textarea class="builder-json" data-builder-field="${blockIndex}" data-key="${escapeHtml(key)}" data-mode="json">${escapeHtml(JSON.stringify(values, null, 2))}</textarea>
          </div>
        `;
      }

      if (isScalarArray) {
        const items = list.length ? list : [createDefaultScalarItem(key)];
        return `
          <div class="builder-full builder-repeater">
            <div class="builder-repeater-toolbar">
              <div>
                <label style="margin:0">${escapeHtml(key)}</label>
                <div class="small">Lista simple editable sin tocar JSON.</div>
              </div>
              <button class="btn btn-secondary" type="button" data-builder-array-action="add-scalar" data-index="${blockIndex}" data-key="${escapeHtml(key)}">Añadir elemento</button>
            </div>
            <div class="builder-inline-list">
              ${items.map((item, itemIndex) => `
                <div class="builder-inline-item">
                  <input value="${escapeHtml(item ?? "")}" data-builder-scalar-item="${blockIndex}" data-key="${escapeHtml(key)}" data-item-index="${itemIndex}">
                  <div class="builder-inline-actions">
                    <button class="btn btn-secondary" type="button" data-builder-array-action="scalar-up" data-index="${blockIndex}" data-key="${escapeHtml(key)}" data-item-index="${itemIndex}">↑</button>
                    <button class="btn btn-secondary" type="button" data-builder-array-action="scalar-down" data-index="${blockIndex}" data-key="${escapeHtml(key)}" data-item-index="${itemIndex}">↓</button>
                    <button class="btn btn-secondary" type="button" data-builder-array-action="scalar-duplicate" data-index="${blockIndex}" data-key="${escapeHtml(key)}" data-item-index="${itemIndex}">Duplicar</button>
                    <button class="btn btn-danger" type="button" data-builder-array-action="scalar-remove" data-index="${blockIndex}" data-key="${escapeHtml(key)}" data-item-index="${itemIndex}">Eliminar</button>
                  </div>
                </div>
              `).join("")}
            </div>
          </div>
        `;
      }

      return `
        <div class="builder-full builder-repeater">
          <div class="builder-repeater-toolbar">
            <div>
              <label style="margin:0">${escapeHtml(key)}</label>
              <div class="small">Edita cards, items y sublistas sin entrar en JSON.</div>
            </div>
            <button class="btn btn-secondary" type="button" data-builder-array-action="add-object" data-index="${blockIndex}" data-key="${escapeHtml(key)}">Añadir item</button>
          </div>
          <div class="builder-repeater-list">
            ${list.map((item, itemIndex) => {
              const title = item.title || item.name || item.text || item.label || item.q || item.category || `Item ${itemIndex + 1}`;
              const scalarFields = [];
              const nestedFields = [];
              Object.entries(item).forEach(([nestedKey, nestedValue]) => {
                if (isSimpleScalarArray(nestedValue)) {
                  nestedFields.push(renderNestedScalarList(blockIndex, key, itemIndex, nestedKey, nestedValue));
                  return;
                }
                if (isSimpleObjectArray(nestedValue)) {
                  nestedFields.push(renderNestedObjectArray(blockIndex, key, itemIndex, nestedKey, nestedValue));
                  return;
                }
                if (isScalarValue(nestedValue)) {
                  const imageField = typeof nestedValue === "string" && isImageLikeKey(nestedKey, key);
                  if (typeof nestedValue === "boolean") {
                    scalarFields.push(`
                      <label class="check builder-full">
                        <input type="checkbox" data-builder-object-field="${blockIndex}" data-key="${escapeHtml(key)}" data-item-index="${itemIndex}" data-nested-key="${escapeHtml(nestedKey)}" data-mode="boolean" ${nestedValue ? "checked" : ""}>
                        ${escapeHtml(nestedKey)}
                      </label>
                    `);
                    return;
                  }
                  if (typeof nestedValue === "number") {
                    scalarFields.push(`
                      <div class="field">
                        <label>${escapeHtml(nestedKey)}</label>
                        <input type="number" value="${escapeHtml(nestedValue)}" data-builder-object-field="${blockIndex}" data-key="${escapeHtml(key)}" data-item-index="${itemIndex}" data-nested-key="${escapeHtml(nestedKey)}" data-mode="number">
                      </div>
                    `);
                    return;
                  }
                  const longField = isLongTextField(nestedKey, nestedValue);
                  if (longField) {
                    scalarFields.push(`
                      <div class="field builder-full">
                        <label>${escapeHtml(nestedKey)}</label>
                        <textarea data-builder-object-field="${blockIndex}" data-key="${escapeHtml(key)}" data-item-index="${itemIndex}" data-nested-key="${escapeHtml(nestedKey)}" data-mode="string">${escapeHtml(nestedValue ?? "")}</textarea>
                        ${imageField ? renderMediaPicker("object", {
                          "target-block": blockIndex,
                          "target-key": key,
                          "target-item-index": itemIndex,
                          "target-nested-key": nestedKey,
                        }) : ""}
                      </div>
                    `);
                  } else {
                    scalarFields.push(`
                      <div class="field ${imageField ? "builder-full" : ""}">
                        <label>${escapeHtml(nestedKey)}</label>
                        <input value="${escapeHtml(nestedValue ?? "")}" data-builder-object-field="${blockIndex}" data-key="${escapeHtml(key)}" data-item-index="${itemIndex}" data-nested-key="${escapeHtml(nestedKey)}" data-mode="string">
                        ${imageField ? renderMediaPicker("object", {
                          "target-block": blockIndex,
                          "target-key": key,
                          "target-item-index": itemIndex,
                          "target-nested-key": nestedKey,
                        }) : ""}
                      </div>
                    `);
                  }
                  return;
                }
                nestedFields.push(`
                  <div class="field builder-full">
                    <label>${escapeHtml(nestedKey)} (JSON)</label>
                    <textarea class="builder-json" data-builder-object-json="${blockIndex}" data-key="${escapeHtml(key)}" data-item-index="${itemIndex}" data-nested-key="${escapeHtml(nestedKey)}">${escapeHtml(JSON.stringify(nestedValue, null, 2))}</textarea>
                  </div>
                `);
              });

              return `
                <article class="builder-repeater-card" draggable="true" data-builder-drag-scope="object" data-builder-drag-block="${blockIndex}" data-builder-drag-key="${escapeHtml(key)}" data-builder-drag-item-index="${itemIndex}">
                  <div class="builder-repeater-card-header">
                    <div class="builder-repeater-card-title">
                      <span class="chip">${itemIndex + 1}</span>
                      <strong>${escapeHtml(title)}</strong>
                    </div>
                    <div class="builder-repeater-card-actions">
                      <button class="btn btn-secondary" type="button" data-builder-array-action="object-up" data-index="${blockIndex}" data-key="${escapeHtml(key)}" data-item-index="${itemIndex}">Subir</button>
                      <button class="btn btn-secondary" type="button" data-builder-array-action="object-down" data-index="${blockIndex}" data-key="${escapeHtml(key)}" data-item-index="${itemIndex}">Bajar</button>
                      <button class="btn btn-secondary" type="button" data-builder-array-action="object-duplicate" data-index="${blockIndex}" data-key="${escapeHtml(key)}" data-item-index="${itemIndex}">Duplicar</button>
                      <button class="btn btn-danger" type="button" data-builder-array-action="object-remove" data-index="${blockIndex}" data-key="${escapeHtml(key)}" data-item-index="${itemIndex}">Eliminar</button>
                    </div>
                  </div>
                  <div class="builder-repeater-fields">
                    ${scalarFields.join("")}
                    ${nestedFields.join("")}
                  </div>
                </article>
              `;
            }).join("")}
          </div>
        </div>
      `;
    }

    function renderBuilderTemplateLibrary() {
      if (!builderTemplateGrid) return;
      if (builderInsertHint) {
        const insertLabel = pendingInsertIndex <= 0
          ? "al principio de la página"
          : pendingInsertIndex >= capsuleState.blocks.length
            ? "al final de la cápsula"
            : `después del bloque ${pendingInsertIndex}`;
        builderInsertHint.textContent = `Elige una plantilla base y el CMS la insertará ${insertLabel}.`;
      }
      builderTemplateGrid.innerHTML = capsuleBuilderTemplates.map((template, index) => `
        <article class="builder-template">
          <span>${escapeHtml(template.category || "Bloque")}</span>
          <strong>${escapeHtml(template.label || template.type || "Block")}</strong>
          <div class="small">${escapeHtml(template.type || "")}</div>
          <button class="btn btn-secondary" type="button" data-builder-add="${index}">Insertar aquí</button>
        </article>
      `).join("");
    }

    function renderBuilderBlocks() {
      if (!builderList) return;
      if (!capsuleState.blocks.length) {
        activeBuilderBlockIndex = -1;
        builderList.innerHTML = '<div class="builder-empty">Todavía no hay bloques en la cápsula. Usa la biblioteca de arriba para añadir uno.</div>';
        renderBuilderContext();
        syncCapsuleTextarea();
        return;
      }
      if (activeBuilderBlockIndex < 0 || activeBuilderBlockIndex >= capsuleState.blocks.length) {
        activeBuilderBlockIndex = 0;
      }
      const pieces = [];
      const renderInsertSlot = (slotIndex) => `
        <div class="builder-insert-slot ${pendingInsertIndex === slotIndex ? "is-active" : ""}">
          <button class="btn btn-secondary" type="button" data-builder-insert-slot="${slotIndex}">+ Insertar aquí</button>
          <div class="small">${slotIndex === 0 ? "Antes del primer bloque" : slotIndex >= capsuleState.blocks.length ? "Después del último bloque" : `Entre ${slotIndex} y ${slotIndex + 1}`}</div>
        </div>
      `;
      pieces.push(renderInsertSlot(0));
      capsuleState.blocks.forEach((block, index) => {
        const scalarFields = [];
        const complexFields = [];
        const styleFields = blockStyleFields.map((field) => renderBlockStyleField(index, field, block.style?.[field.key] ?? ""));
        Object.entries(block.props || {}).forEach(([key, value]) => {
          if (Array.isArray(value)) {
            complexFields.push(renderRepeaterArray(index, key, value));
            return;
          }
          if (value && typeof value === "object") {
            complexFields.push(`
              <div class="field builder-full">
                <label>${escapeHtml(key)} (JSON)</label>
                <textarea class="builder-json" data-builder-field="${index}" data-key="${escapeHtml(key)}" data-mode="json">${escapeHtml(JSON.stringify(value, null, 2))}</textarea>
              </div>
            `);
            return;
          }
          if (typeof value === "boolean") {
            scalarFields.push(`
              <label class="check builder-full">
                <input type="checkbox" data-builder-field="${index}" data-key="${escapeHtml(key)}" data-mode="boolean" ${value ? "checked" : ""}>
                ${escapeHtml(key)}
              </label>
            `);
            return;
          }
          if (typeof value === "number") {
            scalarFields.push(`
              <div class="field">
                <label>${escapeHtml(key)}</label>
                <input type="number" data-builder-field="${index}" data-key="${escapeHtml(key)}" data-mode="number" value="${escapeHtml(value)}">
              </div>
            `);
            return;
          }
          const inputTag = isLongTextField(key, value) ? "textarea" : "input";
          const imageField = typeof value === "string" && isImageLikeKey(key);
          const valueAttr = escapeHtml(value);
          if (inputTag === "textarea") {
            scalarFields.push(`
              <div class="field builder-full">
                <label>${escapeHtml(key)}</label>
                <textarea data-builder-field="${index}" data-key="${escapeHtml(key)}" data-mode="string">${valueAttr}</textarea>
                ${imageField ? renderMediaPicker("field", {
                  "target-block": index,
                  "target-key": key,
                }) : ""}
              </div>
            `);
          } else {
            scalarFields.push(`
              <div class="field ${imageField ? "builder-full" : ""}">
                <label>${escapeHtml(key)}</label>
                <input data-builder-field="${index}" data-key="${escapeHtml(key)}" data-mode="string" value="${valueAttr}">
                ${imageField ? renderMediaPicker("field", {
                  "target-block": index,
                  "target-key": key,
                }) : ""}
              </div>
            `);
          }
        });
        const summaryText = scalarFields.length || complexFields.length
          ? "Haz clic para desplegar y editar contenido, listas, imágenes y estilo."
          : "Este bloque no tiene campos visuales detectados. Puedes seguir editándolo desde el JSON de la cápsula.";
        pieces.push(`
          <article class="builder-block ${activeBuilderBlockIndex === index ? "is-selected" : ""}" data-builder-block="${index}">
            <div class="builder-block-header">
              <div class="builder-block-title">
                <span class="chip">${index + 1} · ${escapeHtml(block.type)}</span>
                <strong>${escapeHtml((block.props && (block.props.title || block.props.brand || block.props.badge)) || block.type)}</strong>
                <div class="small">${escapeHtml(block.id || "")}</div>
              </div>
              <div class="builder-actions">
                <button class="btn btn-secondary" type="button" data-builder-action="select" data-index="${index}">${activeBuilderBlockIndex === index ? "Editando" : "Editar"}</button>
                <button class="btn btn-secondary" type="button" data-builder-action="up" data-index="${index}">Subir</button>
                <button class="btn btn-secondary" type="button" data-builder-action="down" data-index="${index}">Bajar</button>
                <button class="btn btn-secondary" type="button" data-builder-action="duplicate" data-index="${index}">Duplicar</button>
                <button class="btn btn-danger" type="button" data-builder-action="remove" data-index="${index}">Eliminar</button>
              </div>
            </div>
            <div class="builder-block-summary">${escapeHtml(summaryText)}</div>
            <div class="builder-block-body">
              <div class="builder-fields">
                ${scalarFields.join("")}
                ${complexFields.length ? `<div class="builder-full builder-note">Las listas y cards del bloque ya se editan de forma visual. El JSON queda como fallback solo para estructuras especiales.</div>${complexFields.join("")}` : ""}
                <div class="builder-full builder-subsection">
                  <h4>Layout y estilo del bloque</h4>
                  <div class="builder-style-grid">
                    ${styleFields.join("")}
                  </div>
                </div>
              </div>
            </div>
          </article>
        `);
        pieces.push(renderInsertSlot(index + 1));
      });
      builderList.innerHTML = pieces.join("");
      renderBuilderContext();
      syncCapsuleTextarea();
    }

    function addBuilderBlock(templateIndex) {
      if (builderReadOnly) return;
      const template = capsuleBuilderTemplates[templateIndex];
      if (!template) return;
      const insertAt = normalizeInsertIndex(pendingInsertIndex);
      capsuleState.blocks.splice(insertAt, 0, {
        id: createBlockId(),
        type: template.type,
        props: deepClone(template.props || {}),
        style: {},
      });
      markAutosaveDirty("Se añadió una sección.");
      selectBuilderBlock(insertAt, { scroll: true, syncPreview: true });
    }

    function ensureArrayProp(blockIndex, key) {
      capsuleState.blocks[blockIndex].props ||= {};
      if (!Array.isArray(capsuleState.blocks[blockIndex].props[key])) {
        capsuleState.blocks[blockIndex].props[key] = [];
      }
      return capsuleState.blocks[blockIndex].props[key];
    }

    function moveArrayItem(list, fromIndex, toIndex) {
      if (!Array.isArray(list)) return;
      if (fromIndex === toIndex || fromIndex < 0 || toIndex < 0 || fromIndex >= list.length || toIndex > list.length) return;
      const [moved] = list.splice(fromIndex, 1);
      const normalizedIndex = fromIndex < toIndex ? toIndex - 1 : toIndex;
      list.splice(normalizedIndex, 0, moved);
    }

    function clearRepeaterDragClasses() {
      if (!builderList) return;
      builderList.querySelectorAll(".builder-repeater-card.is-dragging,.builder-repeater-card.is-drop-target").forEach((card) => {
        card.classList.remove("is-dragging", "is-drop-target");
      });
    }

    function parseCardDragMeta(card) {
      if (!card) return null;
      const scope = card.dataset.builderDragScope || "";
      const blockIndex = Number(card.dataset.builderDragBlock || -1);
      const key = card.dataset.builderDragKey || "";
      const itemIndex = Number(card.dataset.builderDragItemIndex || -1);
      const parentIndex = Number(card.dataset.builderDragParentIndex || -1);
      const nestedKey = card.dataset.builderDragNestedKey || "";
      if (!scope || blockIndex < 0 || !key || itemIndex < 0) return null;
      return { scope, blockIndex, key, itemIndex, parentIndex, nestedKey };
    }

    function sameRepeaterScope(a, b) {
      if (!a || !b) return false;
      if (a.scope !== b.scope) return false;
      if (a.blockIndex !== b.blockIndex || a.key !== b.key) return false;
      if (a.scope === "nested-object") {
        return a.parentIndex === b.parentIndex && a.nestedKey === b.nestedKey;
      }
      return true;
    }

    function applyMediaSelection(button) {
      if (builderReadOnly) return false;
      const url = button.dataset.mediaUrl || "";
      const scope = button.dataset.builderPickMedia || "";
      if (!url || !scope) return false;
      let selector = "";
      if (scope === "field") {
        selector = `[data-builder-field="${button.dataset.targetBlock || ""}"][data-key="${button.dataset.targetKey || ""}"][data-mode="string"]`;
      } else if (scope === "object") {
        selector = `[data-builder-object-field="${button.dataset.targetBlock || ""}"][data-key="${button.dataset.targetKey || ""}"][data-item-index="${button.dataset.targetItemIndex || ""}"][data-nested-key="${button.dataset.targetNestedKey || ""}"][data-mode="string"]`;
      } else if (scope === "nested-object") {
        selector = `[data-builder-nested-object-field="${button.dataset.targetBlock || ""}"][data-key="${button.dataset.targetKey || ""}"][data-parent-index="${button.dataset.targetParentIndex || ""}"][data-nested-key="${button.dataset.targetNestedKey || ""}"][data-item-index="${button.dataset.targetItemIndex || ""}"][data-deep-key="${button.dataset.targetDeepKey || ""}"][data-mode="string"]`;
      }
      if (!selector) return false;
      const target = builderList ? builderList.querySelector(selector) : null;
      if (!target) return false;
      target.value = url;
      target.dispatchEvent(new Event("input", { bubbles: true }));
      const details = button.closest("details");
      if (details) details.open = false;
      return true;
    }

    async function refreshPreview() {
      if (!preview || !pageEditorForm) return;
      setPreviewLoading(true);
      try {
        const formData = new FormData(pageEditorForm);
        const response = await fetch(previewEndpoint, {
          method: "POST",
          body: formData,
          credentials: "same-origin",
          headers: { "X-Requested-With": "XMLHttpRequest" },
        });
        if (!response.ok) {
          throw new Error(`Preview request failed with ${response.status}`);
        }
        preview.srcdoc = await response.text();
      } catch (error) {
        console.warn("Preview fallback:", error);
        if (!htmlEditor) return;
        preview.srcdoc = buildPreviewDoc(pageTitle ? pageTitle.value : "", htmlEditor.value);
        setPreviewLoading(false);
      }
    }

    function applyBuilderReadOnlyState() {
      if (!builderReadOnly) return;
      if (builderSyncButton) {
        builderSyncButton.disabled = true;
        builderSyncButton.textContent = "Solo lectura";
      }
      [builderTemplateGrid, builderGlobalStyle, builderList, builderContext].forEach((container) => {
        if (!container) return;
        container.querySelectorAll("button, input, select, textarea").forEach((element) => {
          element.disabled = true;
        });
        container.querySelectorAll(".builder-repeater-card[draggable]").forEach((card) => {
          card.setAttribute("draggable", "false");
        });
      });
    }

    document.querySelectorAll("[data-insert-template]").forEach((button) => {
      button.addEventListener("click", () => {
        const index = Number(button.dataset.insertTemplate || -1);
        const template = sectionTemplates[index];
        if (!template || !htmlEditor) return;
        insertAtCursor(htmlEditor, template.html);
        refreshPreview();
      });
    });

    document.querySelectorAll("[data-insert-media]").forEach((button) => {
      button.addEventListener("click", () => {
        const url = button.dataset.insertMedia || "";
        if (!url || !htmlEditor) return;
        insertAtCursor(htmlEditor, `<img src="${url}" alt="" style="width:100%;height:auto;border-radius:18px">`);
        refreshPreview();
      });
    });

    document.querySelectorAll("[data-copy-url]").forEach((button) => {
      button.addEventListener("click", async () => {
        const url = button.dataset.copyUrl || "";
        if (!url) return;
        const originalLabel = button.dataset.copyLabelHtml || button.innerHTML;
        const successLabel = button.dataset.copySuccessLabel || "URL copiada";
        try {
          await navigator.clipboard.writeText(url);
          button.innerHTML = successLabel;
          setTimeout(() => { button.innerHTML = originalLabel; }, 1400);
        } catch (error) {
          console.error(error);
        }
      });
    });

    const pagesSearchInput = document.getElementById("searchPages");
    const mediaSearchInput = document.getElementById("searchMedia");
    const inboxSearchInput = document.getElementById("searchInbox");
    const inboxStatusFilter = document.getElementById("filterInboxStatus");

    function filterPageList() {
      const query = normalizeSearchText(pagesSearchInput?.value || "");
      document.querySelectorAll("#pageList .page-item").forEach((item) => {
        const haystack = normalizeSearchText(item.dataset.pageSearch || item.textContent || "");
        item.classList.toggle("is-filter-hidden", !!query && !haystack.includes(query));
      });
    }

    function filterMediaGrid() {
      const query = normalizeSearchText(mediaSearchInput?.value || "");
      document.querySelectorAll("#mediaGrid .media-card").forEach((card) => {
        const haystack = normalizeSearchText(card.dataset.mediaSearch || card.textContent || "");
        card.classList.toggle("is-filter-hidden", !!query && !haystack.includes(query));
      });
    }

    function filterInboxList() {
      const query = normalizeSearchText(inboxSearchInput?.value || "");
      const wantedStatus = normalizeSearchText(inboxStatusFilter?.value || "");
      document.querySelectorAll("[data-submission-id]").forEach((card) => {
        const haystack = normalizeSearchText(card.dataset.submissionSearch || card.textContent || "");
        const status = normalizeSearchText(card.dataset.submissionStatus || "");
        const matchesQuery = !query || haystack.includes(query);
        const matchesStatus = !wantedStatus || status === wantedStatus;
        card.classList.toggle("is-filter-hidden", !(matchesQuery && matchesStatus));
      });
    }

    pagesSearchInput?.addEventListener("input", filterPageList);
    mediaSearchInput?.addEventListener("input", filterMediaGrid);
    inboxSearchInput?.addEventListener("input", filterInboxList);
    inboxStatusFilter?.addEventListener("change", filterInboxList);

    filterPageList();
    filterMediaGrid();
    filterInboxList();

    document.querySelectorAll("[data-sync-color]").forEach((colorInput) => {
      colorInput.addEventListener("input", () => {
        const targetId = colorInput.getAttribute("data-sync-color");
        const target = targetId ? document.getElementById(targetId) : null;
        if (target) target.value = colorInput.value;
      });
    });

    document.querySelectorAll(".js-refresh-preview").forEach((button) => {
      button.addEventListener("click", refreshPreview);
    });

    if (builderTemplateGrid) {
      builderTemplateGrid.addEventListener("click", (event) => {
        if (builderReadOnly) return;
        const button = event.target.closest("[data-builder-add]");
        if (!button) return;
        addBuilderBlock(Number(button.dataset.builderAdd || -1));
      });
    }

    if (builderList) {
      builderList.addEventListener("click", (event) => {
        const insertSlot = event.target.closest("[data-builder-insert-slot]");
        if (insertSlot) {
          if (builderReadOnly) return;
          const slotIndex = Number(insertSlot.dataset.builderInsertSlot || -1);
          if (slotIndex >= 0) {
            setPendingInsertIndex(slotIndex);
          }
          return;
        }
        const block = event.target.closest("[data-builder-block]");
        const actionButton = event.target.closest("[data-builder-action],[data-builder-array-action],[data-builder-nested-action],[data-builder-pick-media]");
        if (!block || actionButton) return;
        const index = Number(block.dataset.builderBlock || -1);
        if (index >= 0) {
          selectBuilderBlock(index, { scroll: false, syncPreview: true });
        }
      });

      builderList.addEventListener("dragstart", (event) => {
        if (builderReadOnly) return;
        const card = event.target.closest(".builder-repeater-card[draggable='true']");
        const meta = parseCardDragMeta(card);
        if (!card || !meta) return;
        builderDragState = meta;
        card.classList.add("is-dragging");
        if (event.dataTransfer) {
          event.dataTransfer.effectAllowed = "move";
          event.dataTransfer.setData("text/plain", JSON.stringify(meta));
        }
      });

      builderList.addEventListener("dragover", (event) => {
        if (builderReadOnly) return;
        const card = event.target.closest(".builder-repeater-card[draggable='true']");
        const meta = parseCardDragMeta(card);
        if (!card || !meta || !builderDragState || !sameRepeaterScope(builderDragState, meta) || builderDragState.itemIndex === meta.itemIndex) return;
        event.preventDefault();
        clearRepeaterDragClasses();
        card.classList.add("is-drop-target");
        const dragging = builderList.querySelector(".builder-repeater-card.is-dragging");
        if (dragging) dragging.classList.add("is-dragging");
      });

      builderList.addEventListener("dragleave", (event) => {
        if (builderReadOnly) return;
        const card = event.target.closest(".builder-repeater-card[draggable='true']");
        if (!card) return;
        const related = event.relatedTarget;
        if (related && card.contains(related)) return;
        card.classList.remove("is-drop-target");
      });

      builderList.addEventListener("drop", (event) => {
        if (builderReadOnly) return;
        const card = event.target.closest(".builder-repeater-card[draggable='true']");
        const meta = parseCardDragMeta(card);
        if (!card || !meta || !builderDragState || !sameRepeaterScope(builderDragState, meta) || builderDragState.itemIndex === meta.itemIndex) return;
        event.preventDefault();
        const rect = card.getBoundingClientRect();
        const insertAfter = event.clientY > rect.top + rect.height / 2;
        if (meta.scope === "object") {
          const list = ensureArrayProp(meta.blockIndex, meta.key);
          moveArrayItem(list, builderDragState.itemIndex, meta.itemIndex + (insertAfter ? 1 : 0));
        } else if (meta.scope === "nested-object") {
          const list = ensureArrayProp(meta.blockIndex, meta.key);
          if (!isPlainObject(list[meta.parentIndex])) list[meta.parentIndex] = {};
          if (!Array.isArray(list[meta.parentIndex][meta.nestedKey])) list[meta.parentIndex][meta.nestedKey] = [];
          moveArrayItem(list[meta.parentIndex][meta.nestedKey], builderDragState.itemIndex, meta.itemIndex + (insertAfter ? 1 : 0));
        }
        builderDragState = null;
        clearRepeaterDragClasses();
        markAutosaveDirty("Se reordenó una colección.");
        renderBuilderBlocks();
      });

      builderList.addEventListener("dragend", () => {
        if (builderReadOnly) return;
        builderDragState = null;
        clearRepeaterDragClasses();
      });

      builderList.addEventListener("click", (event) => {
        if (builderReadOnly) return;
        const mediaButton = event.target.closest("[data-builder-pick-media]");
        if (!mediaButton) return;
        event.preventDefault();
        applyMediaSelection(mediaButton);
      });

      builderList.addEventListener("click", (event) => {
        if (builderReadOnly) return;
        const button = event.target.closest("[data-builder-action]");
        if (!button) return;
        const index = Number(button.dataset.index || -1);
        if (index < 0 || index >= capsuleState.blocks.length) return;
        const action = button.dataset.builderAction;
        let changed = false;
        if (action === "select") {
          selectBuilderBlock(index, { scroll: true, syncPreview: true });
          return;
        } else if (action === "up" && index > 0) {
          [capsuleState.blocks[index - 1], capsuleState.blocks[index]] = [capsuleState.blocks[index], capsuleState.blocks[index - 1]];
          activeBuilderBlockIndex = index - 1;
          changed = true;
        } else if (action === "down" && index < capsuleState.blocks.length - 1) {
          [capsuleState.blocks[index + 1], capsuleState.blocks[index]] = [capsuleState.blocks[index], capsuleState.blocks[index + 1]];
          activeBuilderBlockIndex = index + 1;
          changed = true;
        } else if (action === "duplicate") {
          const copy = deepClone(capsuleState.blocks[index]);
          copy.id = createBlockId();
          capsuleState.blocks.splice(index + 1, 0, copy);
          activeBuilderBlockIndex = index + 1;
          changed = true;
        } else if (action === "remove") {
          capsuleState.blocks.splice(index, 1);
          activeBuilderBlockIndex = capsuleState.blocks.length ? Math.max(0, Math.min(index, capsuleState.blocks.length - 1)) : -1;
          changed = true;
        }
        if (changed) {
          const messages = {
            up: "Se reordenó una sección.",
            down: "Se reordenó una sección.",
            duplicate: "Se duplicó una sección.",
            remove: "Se eliminó una sección.",
          };
          markAutosaveDirty(messages[action] || "Se actualizó una sección.");
        }
        if (activeBuilderBlockIndex >= 0) {
          selectBuilderBlock(activeBuilderBlockIndex, { scroll: true, syncPreview: true });
        } else {
          renderBuilderBlocks();
        }
      });

      builderList.addEventListener("click", (event) => {
        if (builderReadOnly) return;
        const button = event.target.closest("[data-builder-array-action]");
        if (!button) return;
        const blockIndex = Number(button.dataset.index || -1);
        const key = button.dataset.key || "";
        const itemIndex = Number(button.dataset.itemIndex || -1);
        if (blockIndex < 0 || blockIndex >= capsuleState.blocks.length || !key) return;
        const action = button.dataset.builderArrayAction;
        const list = ensureArrayProp(blockIndex, key);
        let changed = false;
        if (action === "add-scalar") {
          list.push(createDefaultScalarItem(key));
          changed = true;
        } else if (action === "scalar-up" && itemIndex > 0) {
          [list[itemIndex - 1], list[itemIndex]] = [list[itemIndex], list[itemIndex - 1]];
          changed = true;
        } else if (action === "scalar-down" && itemIndex >= 0 && itemIndex < list.length - 1) {
          [list[itemIndex + 1], list[itemIndex]] = [list[itemIndex], list[itemIndex + 1]];
          changed = true;
        } else if (action === "scalar-duplicate" && itemIndex >= 0) {
          list.splice(itemIndex + 1, 0, deepClone(list[itemIndex]));
          changed = true;
        } else if (action === "scalar-remove" && itemIndex >= 0) {
          list.splice(itemIndex, 1);
          changed = true;
        } else if (action === "add-object") {
          const base = list[0] && isPlainObject(list[0]) ? deepClone(list[0]) : createDefaultObjectItem(key);
          list.push(base);
          changed = true;
        } else if (action === "object-up" && itemIndex > 0) {
          [list[itemIndex - 1], list[itemIndex]] = [list[itemIndex], list[itemIndex - 1]];
          changed = true;
        } else if (action === "object-down" && itemIndex >= 0 && itemIndex < list.length - 1) {
          [list[itemIndex + 1], list[itemIndex]] = [list[itemIndex], list[itemIndex + 1]];
          changed = true;
        } else if (action === "object-duplicate" && itemIndex >= 0) {
          list.splice(itemIndex + 1, 0, deepClone(list[itemIndex]));
          changed = true;
        } else if (action === "object-remove" && itemIndex >= 0) {
          list.splice(itemIndex, 1);
          changed = true;
        }
        if (changed) {
          markAutosaveDirty("Se actualizó una colección.");
        }
        renderBuilderBlocks();
      });

      builderList.addEventListener("click", (event) => {
        if (builderReadOnly) return;
        const button = event.target.closest("[data-builder-nested-action]");
        if (!button) return;
        const blockIndex = Number(button.dataset.index || -1);
        const key = button.dataset.key || "";
        const nestedKey = button.dataset.nestedKey || "";
        const parentIndex = Number(button.dataset.parentIndex || -1);
        const itemIndex = Number(button.dataset.itemIndex || -1);
        if (blockIndex < 0 || blockIndex >= capsuleState.blocks.length || !key || !nestedKey || parentIndex < 0) return;
        const list = ensureArrayProp(blockIndex, key);
        if (!isPlainObject(list[parentIndex])) list[parentIndex] = {};
        if (!Array.isArray(list[parentIndex][nestedKey])) list[parentIndex][nestedKey] = [];
        const nestedList = list[parentIndex][nestedKey];
        const action = button.dataset.builderNestedAction;
        let changed = false;
        if (action === "nested-scalar-add") {
          nestedList.push(createDefaultScalarItem(nestedKey));
          changed = true;
        } else if (action === "nested-scalar-up" && itemIndex > 0) {
          [nestedList[itemIndex - 1], nestedList[itemIndex]] = [nestedList[itemIndex], nestedList[itemIndex - 1]];
          changed = true;
        } else if (action === "nested-scalar-down" && itemIndex >= 0 && itemIndex < nestedList.length - 1) {
          [nestedList[itemIndex + 1], nestedList[itemIndex]] = [nestedList[itemIndex], nestedList[itemIndex + 1]];
          changed = true;
        } else if (action === "nested-scalar-remove" && itemIndex >= 0) {
          nestedList.splice(itemIndex, 1);
          changed = true;
        } else if (action === "nested-object-add") {
          nestedList.push(isPlainObject(nestedList[0]) ? deepClone(nestedList[0]) : createDefaultObjectItem(nestedKey));
          changed = true;
        } else if (action === "nested-object-up" && itemIndex > 0) {
          [nestedList[itemIndex - 1], nestedList[itemIndex]] = [nestedList[itemIndex], nestedList[itemIndex - 1]];
          changed = true;
        } else if (action === "nested-object-down" && itemIndex >= 0 && itemIndex < nestedList.length - 1) {
          [nestedList[itemIndex + 1], nestedList[itemIndex]] = [nestedList[itemIndex], nestedList[itemIndex + 1]];
          changed = true;
        } else if (action === "nested-object-duplicate" && itemIndex >= 0) {
          nestedList.splice(itemIndex + 1, 0, deepClone(nestedList[itemIndex]));
          changed = true;
        } else if (action === "nested-object-remove" && itemIndex >= 0) {
          nestedList.splice(itemIndex, 1);
          changed = true;
        }
        if (changed) {
          markAutosaveDirty("Se actualizó una colección anidada.");
        }
        renderBuilderBlocks();
      });

      builderList.addEventListener("input", (event) => {
        if (builderReadOnly) return;
        const field = event.target.closest("[data-builder-field]");
        if (!field) return;
        const index = Number(field.dataset.builderField || -1);
        const key = field.dataset.key || "";
        if (index < 0 || index >= capsuleState.blocks.length || !key) return;
        const mode = field.dataset.mode || "string";
        if (mode === "boolean") {
          capsuleState.blocks[index].props[key] = !!field.checked;
        } else if (mode === "number") {
          capsuleState.blocks[index].props[key] = field.value === "" ? 0 : Number(field.value);
        } else if (mode === "string") {
          capsuleState.blocks[index].props[key] = field.value;
        }
        syncCapsuleTextarea();
      });

      builderList.addEventListener("input", (event) => {
        if (builderReadOnly) return;
        const field = event.target.closest("[data-builder-scalar-item]");
        if (!field) return;
        const blockIndex = Number(field.dataset.builderScalarItem || -1);
        const key = field.dataset.key || "";
        const itemIndex = Number(field.dataset.itemIndex || -1);
        if (blockIndex < 0 || blockIndex >= capsuleState.blocks.length || !key || itemIndex < 0) return;
        const list = ensureArrayProp(blockIndex, key);
        list[itemIndex] = field.value;
        syncCapsuleTextarea();
      });

      builderList.addEventListener("input", (event) => {
        if (builderReadOnly) return;
        const field = event.target.closest("[data-builder-object-field]");
        if (!field) return;
        const blockIndex = Number(field.dataset.builderObjectField || -1);
        const key = field.dataset.key || "";
        const itemIndex = Number(field.dataset.itemIndex || -1);
        const nestedKey = field.dataset.nestedKey || "";
        if (blockIndex < 0 || blockIndex >= capsuleState.blocks.length || !key || !nestedKey || itemIndex < 0) return;
        const list = ensureArrayProp(blockIndex, key);
        if (!isPlainObject(list[itemIndex])) list[itemIndex] = {};
        const mode = field.dataset.mode || "string";
        if (mode === "boolean") {
          list[itemIndex][nestedKey] = !!field.checked;
        } else if (mode === "number") {
          list[itemIndex][nestedKey] = field.value === "" ? 0 : Number(field.value);
        } else {
          list[itemIndex][nestedKey] = field.value;
        }
        syncCapsuleTextarea();
      });

      builderList.addEventListener("input", (event) => {
        if (builderReadOnly) return;
        const field = event.target.closest("[data-builder-style-field]");
        if (!field) return;
        const index = Number(field.dataset.builderStyleField || -1);
        const key = field.dataset.key || "";
        if (index < 0 || index >= capsuleState.blocks.length || !key) return;
        const mode = field.dataset.mode || "text";
        field.parentElement?.querySelectorAll?.(`[data-builder-style-field="${index}"][data-key="${key}"]`).forEach((peer) => {
          if (peer !== field && peer.value !== field.value) peer.value = field.value;
        });
        capsuleState.blocks[index].style ||= {};
        if (field.value === "") {
          delete capsuleState.blocks[index].style[key];
        } else if (mode === "number") {
          capsuleState.blocks[index].style[key] = Number(field.value);
        } else {
          capsuleState.blocks[index].style[key] = field.value;
        }
        syncCapsuleTextarea();
      });

      builderList.addEventListener("change", (event) => {
        if (builderReadOnly) return;
        const field = event.target.closest("[data-builder-field][data-mode='json']");
        if (!field) return;
        const index = Number(field.dataset.builderField || -1);
        const key = field.dataset.key || "";
        if (index < 0 || index >= capsuleState.blocks.length || !key) return;
        try {
          capsuleState.blocks[index].props[key] = JSON.parse(field.value || "[]");
          field.style.borderColor = "";
          syncCapsuleTextarea();
        } catch (error) {
          field.style.borderColor = "var(--danger)";
          window.alert(`El JSON del campo "${key}" no es válido.`);
        }
      });

      builderList.addEventListener("input", (event) => {
        if (builderReadOnly) return;
        const field = event.target.closest("[data-builder-nested-scalar]");
        if (!field) return;
        const blockIndex = Number(field.dataset.builderNestedScalar || -1);
        const key = field.dataset.key || "";
        const nestedKey = field.dataset.nestedKey || "";
        const itemIndex = Number(field.dataset.itemIndex || -1);
        const parentIndex = Number(field.dataset.parentIndex || -1);
        if (blockIndex < 0 || blockIndex >= capsuleState.blocks.length || !key || !nestedKey || itemIndex < 0 || parentIndex < 0) return;
        const list = ensureArrayProp(blockIndex, key);
        if (!isPlainObject(list[parentIndex])) list[parentIndex] = {};
        if (!Array.isArray(list[parentIndex][nestedKey])) list[parentIndex][nestedKey] = [];
        list[parentIndex][nestedKey][itemIndex] = field.value;
        syncCapsuleTextarea();
      });

      builderList.addEventListener("input", (event) => {
        if (builderReadOnly) return;
        const field = event.target.closest("[data-builder-nested-object-field]");
        if (!field) return;
        const blockIndex = Number(field.dataset.builderNestedObjectField || -1);
        const key = field.dataset.key || "";
        const nestedKey = field.dataset.nestedKey || "";
        const parentIndex = Number(field.dataset.parentIndex || -1);
        const itemIndex = Number(field.dataset.itemIndex || -1);
        const deepKey = field.dataset.deepKey || "";
        if (blockIndex < 0 || blockIndex >= capsuleState.blocks.length || !key || !nestedKey || parentIndex < 0 || itemIndex < 0 || !deepKey) return;
        const list = ensureArrayProp(blockIndex, key);
        if (!isPlainObject(list[parentIndex])) list[parentIndex] = {};
        if (!Array.isArray(list[parentIndex][nestedKey])) list[parentIndex][nestedKey] = [];
        if (!isPlainObject(list[parentIndex][nestedKey][itemIndex])) list[parentIndex][nestedKey][itemIndex] = {};
        const mode = field.dataset.mode || "string";
        if (mode === "boolean") {
          list[parentIndex][nestedKey][itemIndex][deepKey] = !!field.checked;
        } else if (mode === "number") {
          list[parentIndex][nestedKey][itemIndex][deepKey] = field.value === "" ? 0 : Number(field.value);
        } else {
          list[parentIndex][nestedKey][itemIndex][deepKey] = field.value;
        }
        syncCapsuleTextarea();
      });

      builderList.addEventListener("change", (event) => {
        if (builderReadOnly) return;
        const field = event.target.closest("[data-builder-object-json]");
        if (!field) return;
        const blockIndex = Number(field.dataset.builderObjectJson || -1);
        const key = field.dataset.key || "";
        const itemIndex = Number(field.dataset.itemIndex || -1);
        const nestedKey = field.dataset.nestedKey || "";
        if (blockIndex < 0 || blockIndex >= capsuleState.blocks.length || !key || !nestedKey || itemIndex < 0) return;
        const list = ensureArrayProp(blockIndex, key);
        if (!isPlainObject(list[itemIndex])) list[itemIndex] = {};
        try {
          list[itemIndex][nestedKey] = JSON.parse(field.value || "null");
          field.style.borderColor = "";
          syncCapsuleTextarea();
        } catch (error) {
          field.style.borderColor = "var(--danger)";
          window.alert(`El JSON del campo "${nestedKey}" no es válido.`);
        }
      });

      builderList.addEventListener("change", (event) => {
        if (builderReadOnly) return;
        const field = event.target.closest("[data-builder-nested-object-json]");
        if (!field) return;
        const blockIndex = Number(field.dataset.builderNestedObjectJson || -1);
        const key = field.dataset.key || "";
        const nestedKey = field.dataset.nestedKey || "";
        const parentIndex = Number(field.dataset.parentIndex || -1);
        const itemIndex = Number(field.dataset.itemIndex || -1);
        const deepKey = field.dataset.deepKey || "";
        if (blockIndex < 0 || blockIndex >= capsuleState.blocks.length || !key || !nestedKey || parentIndex < 0 || itemIndex < 0 || !deepKey) return;
        const list = ensureArrayProp(blockIndex, key);
        if (!isPlainObject(list[parentIndex])) list[parentIndex] = {};
        if (!Array.isArray(list[parentIndex][nestedKey])) list[parentIndex][nestedKey] = [];
        if (!isPlainObject(list[parentIndex][nestedKey][itemIndex])) list[parentIndex][nestedKey][itemIndex] = {};
        try {
          list[parentIndex][nestedKey][itemIndex][deepKey] = JSON.parse(field.value || "null");
          field.style.borderColor = "";
          syncCapsuleTextarea();
        } catch (error) {
          field.style.borderColor = "var(--danger)";
          window.alert(`El JSON del campo "${deepKey}" no es válido.`);
        }
      });
    }

    if (builderGlobalStyle) {
      builderGlobalStyle.addEventListener("input", (event) => {
        if (builderReadOnly) return;
        const field = event.target.closest("[data-builder-global-style]");
        if (!field) return;
        const key = field.dataset.builderGlobalStyle || "";
        if (!key) return;
        field.parentElement?.querySelectorAll?.(`[data-builder-global-style="${key}"]`).forEach((peer) => {
          if (peer !== field && peer.value !== field.value) peer.value = field.value;
        });
        if (field.value === "") {
          delete capsuleState.style[key];
        } else {
          capsuleState.style[key] = field.value;
        }
        syncCapsuleTextarea();
      });
    }

    if (builderSyncButton) {
      builderSyncButton.addEventListener("click", () => {
        if (builderReadOnly) return;
        syncCapsuleTextarea();
        window.alert("JSON avanzado sincronizado con las secciones.");
      });
    }

    if (builderContext) {
      builderContext.addEventListener("click", (event) => {
        if (builderReadOnly) return;
        const button = event.target.closest("[data-builder-context]");
        if (!button || activeBuilderBlockIndex < 0) return;
        const action = button.dataset.builderContext || "";
        if (action === "content") {
          selectBuilderBlock(activeBuilderBlockIndex, { scroll: true, syncPreview: true });
          focusSelectedBlockField("content");
          return;
        }
        if (action === "link") {
          selectBuilderBlock(activeBuilderBlockIndex, { scroll: true, syncPreview: true });
          focusSelectedBlockLinkField();
          return;
        }
        if (action === "media") {
          selectBuilderBlock(activeBuilderBlockIndex, { scroll: true, syncPreview: true });
          openPreviewMediaPicker();
          return;
        }
        if (action === "style") {
          selectBuilderBlock(activeBuilderBlockIndex, { scroll: true, syncPreview: true });
          focusSelectedBlockField("style");
          return;
        }
        if (action === "insert") {
          setPendingInsertIndex(activeBuilderBlockIndex + 1, { render: false });
          renderBuilderTemplateLibrary();
          renderBuilderContext();
          renderBuilderBlocks();
          builderTemplateGrid?.scrollIntoView({ block: "center", behavior: "smooth" });
          return;
        }
        if (action === "duplicate") {
          const copy = deepClone(capsuleState.blocks[activeBuilderBlockIndex]);
          copy.id = createBlockId();
          capsuleState.blocks.splice(activeBuilderBlockIndex + 1, 0, copy);
          markAutosaveDirty("Se duplicó una sección.");
          selectBuilderBlock(activeBuilderBlockIndex + 1, { scroll: true, syncPreview: true });
          return;
        }
        if (action === "remove") {
          capsuleState.blocks.splice(activeBuilderBlockIndex, 1);
          markAutosaveDirty("Se eliminó una sección.");
          if (capsuleState.blocks.length) {
            selectBuilderBlock(Math.max(0, Math.min(activeBuilderBlockIndex, capsuleState.blocks.length - 1)), { scroll: true, syncPreview: true });
          } else {
            renderBuilderBlocks();
          }
        }
      });
    }

    [htmlEditor, pageTitle].forEach((input) => {
      if (!input) return;
      input.addEventListener("input", () => {
        schedulePreviewRefresh();
      });
    });

    if (pageEditorForm) {
      pageEditorForm.addEventListener("input", (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) return;
        if (target === pageAutosaveFlag) return;
        markAutosaveDirty();
      });
      pageEditorForm.addEventListener("change", (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) return;
        if (target === pageAutosaveFlag) return;
        markAutosaveDirty();
      });
      pageEditorForm.addEventListener("submit", () => {
        window.clearTimeout(autosaveTimer);
        autosaveDirty = false;
        if (pageAutosaveFlag) {
          pageAutosaveFlag.value = "0";
        }
        setAutosaveState("saving", "Guardando cambios manualmente…");
        syncCapsuleTextarea();
      });
    }

    const toastContainer = document.getElementById("toastContainer");
    const confirmModalBackdrop = document.getElementById("confirmModalBackdrop");
    const confirmModalTitle = document.getElementById("confirmModalTitle");
    const confirmModalMessage = document.getElementById("confirmModalMessage");
    const confirmModalCancel = document.getElementById("confirmModalCancel");
    const confirmModalConfirm = document.getElementById("confirmModalConfirm");
    let confirmTargetForm = null;
    let lastConfirmTrigger = null;

    function createToast(type, message, title = "") {
      if (!toastContainer || !message) return;
      const toast = document.createElement("div");
      toast.className = `toast toast--${type || "info"}`;
      const copy = document.createElement("div");
      copy.className = "toast-copy";
      if (title) {
        const strong = document.createElement("strong");
        strong.textContent = title;
        copy.appendChild(strong);
      }
      const text = document.createElement("span");
      text.textContent = message;
      copy.appendChild(text);
      toast.appendChild(copy);
      toastContainer.appendChild(toast);
      window.setTimeout(() => {
        toast.remove();
      }, 3200);
    }

    document.querySelectorAll(".flash").forEach((flash) => {
      const message = (flash.textContent || "").trim();
      if (!message) return;
      const type = flash.classList.contains("error") ? "error" : flash.classList.contains("success") ? "success" : "info";
      createToast(type, message, type === "error" ? "Error" : type === "success" ? "Hecho" : "Aviso");
      flash.classList.add("flash--converted");
    });

    function setButtonLoading(button, loading) {
      if (!(button instanceof HTMLElement)) return;
      if (loading) {
        button.classList.add("is-loading");
        button.setAttribute("aria-busy", "true");
        button.dataset.originalDisabled = button.disabled ? "1" : "0";
        button.disabled = true;
      } else {
        button.classList.remove("is-loading");
        button.removeAttribute("aria-busy");
        if (button.dataset.originalDisabled !== "1") {
          button.disabled = false;
        }
        delete button.dataset.originalDisabled;
      }
    }

    function closeConfirmModal() {
      if (!confirmModalBackdrop) return;
      confirmModalBackdrop.hidden = true;
      if (lastConfirmTrigger && typeof lastConfirmTrigger.focus === "function") {
        lastConfirmTrigger.focus();
      }
      confirmTargetForm = null;
      lastConfirmTrigger = null;
    }

    function openConfirmModal(form, trigger) {
      if (!confirmModalBackdrop || !confirmModalTitle || !confirmModalMessage) return;
      confirmTargetForm = form;
      lastConfirmTrigger = trigger || null;
      confirmModalTitle.textContent = form.dataset.confirmTitle || "¿Confirmar acción?";
      confirmModalMessage.textContent = form.dataset.confirmMessage || "Esta acción no se puede deshacer.";
      confirmModalBackdrop.hidden = false;
      window.setTimeout(() => confirmModalConfirm?.focus(), 0);
    }

    confirmModalCancel?.addEventListener("click", () => {
      closeConfirmModal();
    });

    confirmModalBackdrop?.addEventListener("click", (event) => {
      if (event.target === confirmModalBackdrop) {
        closeConfirmModal();
      }
    });

    window.addEventListener("keydown", (event) => {
      if (event.key === "Escape" && confirmModalBackdrop && !confirmModalBackdrop.hidden) {
        event.preventDefault();
        closeConfirmModal();
      }
    });

    confirmModalConfirm?.addEventListener("click", () => {
      if (!confirmTargetForm) return;
      confirmTargetForm.dataset.confirmed = "1";
      const submitter = confirmTargetForm.querySelector('button[type="submit"], input[type="submit"]');
      setButtonLoading(submitter, true);
      closeConfirmModal();
      if (typeof confirmTargetForm.requestSubmit === "function") {
        confirmTargetForm.requestSubmit();
      } else {
        confirmTargetForm.submit();
      }
    });

    document.addEventListener("submit", (event) => {
      const form = event.target;
      if (!(form instanceof HTMLFormElement)) return;
      if ((form.method || "get").toLowerCase() !== "post") return;
      if (form.dataset.confirmTitle && form.dataset.confirmed !== "1") {
        event.preventDefault();
        openConfirmModal(form, event.submitter || document.activeElement);
        return;
      }
      if (form.dataset.confirmed === "1") {
        delete form.dataset.confirmed;
      }
      const submitter = event.submitter || form.querySelector('button[type="submit"], input[type="submit"]');
      setButtonLoading(submitter, true);
    }, true);

    function getPrimarySubmitter(form) {
      if (!(form instanceof HTMLFormElement)) return null;
      return form.querySelector('button[type="submit"], input[type="submit"]');
    }

    function getPreferredSaveForm() {
      return [pageEditorForm, postEditorForm, siteSettingsForm].find((form) => (
        form instanceof HTMLFormElement
        && form.offsetParent !== null
      )) || null;
    }

    window.addEventListener("keydown", (event) => {
      if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === "s") {
        const targetForm = getPreferredSaveForm();
        if (!targetForm) return;
        event.preventDefault();
        const submitter = getPrimarySubmitter(targetForm);
        setButtonLoading(submitter, true);
        createToast("info", "Guardando cambios…", "Atajo de teclado");
        if (typeof targetForm.requestSubmit === "function") {
          targetForm.requestSubmit(submitter || undefined);
        } else {
          targetForm.submit();
        }
        return;
      }
      if (event.key === "Escape") {
        const mediaBackdrop = document.getElementById("media-modal-backdrop");
        if ((confirmModalBackdrop && !confirmModalBackdrop.hidden) || (mediaBackdrop && mediaBackdrop.classList.contains("is-open"))) {
          return;
        }
        const activeElement = document.activeElement;
        if (activeElement instanceof HTMLElement && ["INPUT", "TEXTAREA", "SELECT"].includes(activeElement.tagName)) {
          activeElement.blur();
        }
      }
    });

    if (preview) {
      preview.addEventListener("load", () => {
        setPreviewLoading(false);
        if (activeBuilderBlockIndex >= 0) {
          window.setTimeout(() => highlightPreviewBlock(activeBuilderBlockIndex), 40);
        }
      });
    }

    window.addEventListener("message", (event) => {
      const payload = event.data || {};
      if (payload && payload.type === "ccms-preview-select-block") {
        const index = Number(payload.index || -1);
        if (index >= 0) {
          selectBuilderBlock(index, { scroll: true, syncPreview: false });
        }
        return;
      }
      if (payload && payload.type === "ccms-preview-quick-text") {
        const index = Number(payload.index || -1);
        if (index < 0) return;
        selectBuilderBlock(index, { scroll: true, syncPreview: false });
        if (builderReadOnly) return;
        focusSelectedBlockTextField(String(payload.text || ""), String(payload.tag || ""));
        return;
      }
      if (payload && payload.type === "ccms-preview-apply-text") {
        const index = Number(payload.index || -1);
        if (index < 0) return;
        selectBuilderBlock(index, { scroll: true, syncPreview: false });
        if (builderReadOnly) return;
        applySelectedBlockTextField(String(payload.oldText || ""), String(payload.newText || ""), String(payload.tag || ""));
        return;
      }
      if (payload && payload.type === "ccms-preview-quick-media") {
        const index = Number(payload.index || -1);
        if (index < 0) return;
        selectBuilderBlock(index, { scroll: true, syncPreview: false });
        if (builderReadOnly) return;
        openPreviewMediaPicker(String(payload.src || ""));
        return;
      }
      if (payload && payload.type === "ccms-preview-quick-link") {
        const index = Number(payload.index || -1);
        if (index < 0) return;
        selectBuilderBlock(index, { scroll: true, syncPreview: false });
        if (builderReadOnly) return;
        focusSelectedBlockLinkField(String(payload.href || ""), String(payload.text || ""));
        return;
      }
      if (payload && payload.type === "ccms-preview-apply-link") {
        const index = Number(payload.index || -1);
        if (index < 0) return;
        selectBuilderBlock(index, { scroll: true, syncPreview: false });
        if (builderReadOnly) return;
        applySelectedBlockLinkField(String(payload.oldHref || ""), String(payload.newHref || ""), String(payload.text || payload.oldText || ""));
        if (String(payload.oldText || "") !== String(payload.newText || "")) {
          applySelectedBlockTextField(String(payload.oldText || ""), String(payload.newText || ""), String(payload.tag || "a"));
        }
        return;
      }
      if (payload && payload.type === "ccms-preview-apply-button") {
        const index = Number(payload.index || -1);
        if (index < 0) return;
        selectBuilderBlock(index, { scroll: true, syncPreview: false });
        if (builderReadOnly) return;
        if (String(payload.oldHref || "") || String(payload.newHref || "")) {
          applySelectedBlockLinkField(String(payload.oldHref || ""), String(payload.newHref || ""), String(payload.oldText || payload.text || ""));
        }
        if (String(payload.oldText || "") !== String(payload.newText || "")) {
          applySelectedBlockTextField(String(payload.oldText || ""), String(payload.newText || ""), String(payload.tag || "button"));
        }
        applySelectedBlockButtonStyle({
          button_bg: String(payload.buttonBg || ""),
          button_text_color: String(payload.buttonTextColor || ""),
          button_variant: Number(payload.ghost || 0) ? "ghost" : "",
        });
        return;
      }
      if (payload && payload.type === "ccms-preview-action") {
        const index = Number(payload.index || -1);
        if (index < 0) return;
        selectBuilderBlock(index, { scroll: true, syncPreview: false });
        if (builderReadOnly) return;
        const action = payload.action || "";
        if (action === "content") {
          focusSelectedBlockField("content");
          return;
        }
        if (action === "link") {
          focusSelectedBlockLinkField(String(payload.href || ""), String(payload.text || ""));
          return;
        }
        if (action === "media") {
          openPreviewMediaPicker(String(payload.src || ""));
          return;
        }
        if (action === "style") {
          focusSelectedBlockField("style");
          return;
        }
        if (action === "insert") {
          setPendingInsertIndex(index + 1, { render: false });
          renderBuilderTemplateLibrary();
          renderBuilderContext();
          renderBuilderBlocks();
          builderTemplateGrid?.scrollIntoView({ block: "center", behavior: "smooth" });
          return;
        }
        if (action === "duplicate") {
          const copy = deepClone(capsuleState.blocks[index]);
          copy.id = createBlockId();
          capsuleState.blocks.splice(index + 1, 0, copy);
          markAutosaveDirty("Se duplicó una sección.");
          selectBuilderBlock(index + 1, { scroll: true, syncPreview: true });
          return;
        }
        if (action === "remove") {
          capsuleState.blocks.splice(index, 1);
          markAutosaveDirty("Se eliminó una sección.");
          if (capsuleState.blocks.length) {
            selectBuilderBlock(Math.max(0, Math.min(index, capsuleState.blocks.length - 1)), { scroll: true, syncPreview: true });
          } else {
            renderBuilderBlocks();
          }
        }
      }
    });

    renderBuilderTemplateLibrary();
    renderBuilderGlobalStyle();
    renderBuilderBlocks();
    applyBuilderReadOnlyState();
    setClientMode(getInitialClientMode());
    if (autosaveMeta && !autosaveMeta.textContent.trim()) {
      setAutosaveState("saved", "Sin cambios pendientes.");
    }
    if (activeBuilderBlockIndex >= 0) {
      highlightPreviewBlock(activeBuilderBlockIndex);
    }
