const { test, expect } = require('@playwright/test');
const { mkdtempSync, cpSync, rmSync, existsSync } = require('fs');
const { tmpdir } = require('os');
const path = require('path');
const net = require('net');
const { spawn } = require('child_process');

function makeRuntimeCopy() {
  const sourceRoot = path.resolve(__dirname, '..', '..');
  const runtimeRoot = mkdtempSync(path.join(tmpdir(), 'linuxcms-e2e-'));
  cpSync(sourceRoot, runtimeRoot, {
    recursive: true,
    filter(src) {
      const rel = path.relative(sourceRoot, src);
      if (!rel) return true;
      const normalized = rel.split(path.sep).join('/');
      if (normalized === '.git') return false;
      if (normalized.startsWith('.git/')) return false;
      if (normalized === 'node_modules') return false;
      if (normalized.startsWith('node_modules/')) return false;
      if (normalized === '.linuxcms-runtime') return false;
      if (normalized.startsWith('.linuxcms-runtime/')) return false;
      if (normalized === 'playwright-report') return false;
      if (normalized.startsWith('playwright-report/')) return false;
      if (normalized === 'test-results') return false;
      if (normalized.startsWith('test-results/')) return false;
      return true;
    },
  });
  for (const rel of ['data/app.json', 'data/app.sqlite', 'data/storage.json']) {
    const file = path.join(runtimeRoot, rel);
    if (existsSync(file)) rmSync(file, { force: true });
  }
  return runtimeRoot;
}

async function reservePort() {
  return await new Promise((resolve, reject) => {
    const server = net.createServer();
    server.listen(0, '127.0.0.1', () => {
      const address = server.address();
      if (!address || typeof address === 'string') {
        server.close();
        reject(new Error('Could not reserve a local port.'));
        return;
      }
      const { port } = address;
      server.close((error) => {
        if (error) {
          reject(error);
          return;
        }
        resolve(port);
      });
    });
    server.on('error', reject);
  });
}

async function waitForReady(baseUrl, timeoutMs = 15_000) {
  const deadline = Date.now() + timeoutMs;
  while (Date.now() < deadline) {
    try {
      const response = await fetch(`${baseUrl}/api/health`);
      if (response.ok) {
        return;
      }
    } catch {
      // keep polling
    }
    await new Promise((resolve) => setTimeout(resolve, 250));
  }
  throw new Error(`LinuxCMS server did not become ready at ${baseUrl}.`);
}

test.describe('LinuxCMS admin smoke', () => {
  let runtimeRoot;
  let serverProcess;
  let baseUrl;

  test.beforeAll(async () => {
    runtimeRoot = makeRuntimeCopy();
    const port = await reservePort();
    baseUrl = `http://127.0.0.1:${port}`;
    serverProcess = spawn('php', ['-S', `127.0.0.1:${port}`, 'router.php'], {
      cwd: runtimeRoot,
      env: {
        ...process.env,
        CCMS_ROOT: runtimeRoot,
      },
      stdio: 'ignore',
    });
    await waitForReady(baseUrl);
  });

  test.afterAll(async () => {
    if (serverProcess && !serverProcess.killed) {
      serverProcess.kill('SIGTERM');
      await new Promise((resolve) => setTimeout(resolve, 500));
    }
    if (runtimeRoot) {
      rmSync(runtimeRoot, { recursive: true, force: true });
    }
  });

  test('installs, logs in and creates a page from the admin', async ({ page }) => {
    await page.goto(`${baseUrl}/install.php`);
    await expect(page.getByRole('heading', { name: 'Instalación rápida' })).toBeVisible();

    await page.getByLabel('Nombre del sitio').fill('LinuxCMS E2E');
    await page.getByLabel('Subtítulo').fill('A smoke-tested install flow.');
    await page.getByLabel('Email del administrador').fill('owner@example.com');
    await page.getByLabel('Usuario').fill('owner');
    await page.locator('#admin_password').fill('PasswordDemo2026!');
    await page.locator('#admin_password_confirm').fill('PasswordDemo2026!');
    await page.getByLabel('Almacenamiento').selectOption('json');
    await page.getByRole('button', { name: 'Instalar y abrir el admin' }).click();

    await expect(page).toHaveURL(/\/r-admin\/$/);
    await expect(page.getByRole('heading', { name: 'Entrar al panel' })).toBeVisible();

    await page.getByLabel('Usuario').fill('owner');
    await page.getByLabel('Contraseña').fill('PasswordDemo2026!');
    await page.getByRole('button', { name: 'Entrar' }).click();

    await expect(page.getByRole('heading', { name: 'r-admin' })).toBeVisible();
    await page.goto(`${baseUrl}/r-admin/?tab=pages`);
    const nav = page.locator('nav.nav-tabs');
    await expect(nav.getByRole('link', { name: 'Páginas', exact: true })).toBeVisible();
    await expect(nav.getByRole('link', { name: 'Cuenta', exact: true })).toBeVisible();

    await page.locator('input[name=\"title\"]').first().fill('Landing legal');
    await page.locator('input[name=\"slug\"]').first().fill('landing-legal');
    await page.getByRole('button', { name: 'Crear página' }).click();

    await expect(page).toHaveURL(/tab=pages&page=landing-legal/);
    await expect(page.getByRole('heading', { name: 'Landing legal' })).toBeVisible();
    await expect(page.locator('#pagePreview')).toBeVisible();
  });
});
