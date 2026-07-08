import { chromium } from 'playwright';
import { mkdir, readFile } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, '..');
const outputDir = path.join(root, 'docs', 'screenshots');
const baseUrl = process.env.SCREENSHOT_BASE_URL ?? 'http://127.0.0.1:8000';

const paths = JSON.parse(
    await readFile(path.join(outputDir, 'paths.json'), 'utf8'),
);

await mkdir(outputDir, { recursive: true });

const browser = await chromium.launch();
const context = await browser.newContext({
    viewport: { width: 1440, height: 900 },
    locale: 'ar-SA',
});
const page = await context.newPage();

async function waitForApp() {
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(800);
}

async function loginAsProjectLead() {
    await page.goto(`${baseUrl}/`, { waitUntil: 'networkidle' });
    await waitForApp();

    const leadButton = page.getByRole('button', { name: 'قائد المشروع' });

    if (await leadButton.count()) {
        await leadButton.click();
        await page.waitForURL((url) => url.pathname !== '/', {
            timeout: 15000,
        });
        await waitForApp();

        return;
    }

    const cookies = await context.cookies();
    const xsrf = cookies.find((cookie) => cookie.name === 'XSRF-TOKEN')?.value;

    if (!xsrf) {
        throw new Error('Could not find XSRF-TOKEN cookie for demo login.');
    }

    await page.request.post(`${baseUrl}/demo-login`, {
        headers: {
            'X-XSRF-TOKEN': decodeURIComponent(xsrf),
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'text/html,application/xhtml+xml',
        },
        form: {
            email: 'project-lead@teamhub.test',
        },
    });

    await page.goto(`${baseUrl}/my-tasks`, { waitUntil: 'networkidle' });
    await waitForApp();
}

async function screenshot(name, urlPath, options = {}) {
    await page.goto(`${baseUrl}${urlPath}`, { waitUntil: 'networkidle' });
    await waitForApp();

    const filePath = path.join(outputDir, `${name}.png`);
    await page.screenshot({
        path: filePath,
        fullPage: options.fullPage ?? false,
    });

    console.log(`saved ${filePath}`);
}

await screenshot('01-entry-ar', paths.paths.entry);

await loginAsProjectLead();
await screenshot('02-my-tasks-ar', paths.paths.my_tasks);
await screenshot('03-workspace-ar', paths.paths.workspace);
await screenshot('04-task-list-ar', paths.paths.task_list, { fullPage: true });

if (paths.paths.task_detail) {
    await screenshot('05-task-detail-ar', paths.paths.task_detail, {
        fullPage: true,
    });
}

await page.setViewportSize({ width: 390, height: 844 });
await screenshot('06-task-list-mobile-ar', paths.paths.task_list, {
    fullPage: true,
});

await context.addCookies([
    {
        name: 'locale',
        value: 'en',
        domain: '127.0.0.1',
        path: '/',
    },
]);

await page.setViewportSize({ width: 1440, height: 900 });
await page.goto(`${baseUrl}${paths.paths.task_list}`, {
    waitUntil: 'networkidle',
});
await waitForApp();
await screenshot('07-task-list-en', paths.paths.task_list, { fullPage: true });

await browser.close();
