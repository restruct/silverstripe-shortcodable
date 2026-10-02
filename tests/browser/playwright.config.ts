import { defineConfig, devices } from '@playwright/test';

// Browser tests for shortcodable's TinyMCE button and dialog. They replace the manual "rung 5" check in
// the module-update SOP. Run them through the shared runner, which builds the scratch hosts and
// starts php -S for each Silverstripe major:
//
//   ~/Sites/0_ss-mods-maintenance/tools/browser/run.sh silverstripe-shortcodable
//
// The runner passes the hosts as BROWSER_TARGET_URLS="ss5=http://127.0.0.1:8897,ss6=...".
// CI does the same with one target per job (.github/workflows/browser-tests.yml).
// Every target becomes one Playwright project, plus a "<target>-login" setup project that logs in
// through the real login form once and saves the session for that target's specs.

const raw = process.env.BROWSER_TARGET_URLS ?? '';
const targets = raw
    .split(',')
    .map((pair) => pair.trim())
    .filter(Boolean)
    .map((pair) => {
        const [name, url] = pair.split('=');
        return { name, url };
    });

if (targets.length === 0) {
    throw new Error(
        'BROWSER_TARGET_URLS is empty. Run through tools/browser/run.sh, or set it by hand, e.g. ' +
            'BROWSER_TARGET_URLS="ss6=http://127.0.0.1:8898" npx playwright test',
    );
}

export default defineConfig({
    testDir: './specs',
    outputDir: './test-results',
    // No retries: a check that only passes on the second try is a flake, and it must show up red.
    retries: 0,
    // One worker: on SS 6.1+ the FileSessionHandler does not lock the session file, so parallel
    // specs sharing one login overwrite each other's session writes (measured on copybutton).
    workers: 1,
    timeout: 30_000,
    expect: { timeout: 7_500 },
    // On GitHub Actions (CI=true) the 'github' reporter also annotates a failing spec on the run
    // summary; locally the output is unchanged.
    reporter: [
        ['list'],
        ...(process.env.CI ? [['github'] as ['github']] : []),
        ['html', { open: 'never', outputFolder: 'playwright-report' }],
    ],
    use: {
        ...devices['Desktop Chrome'],
        // Failure evidence: a screenshot and a trace (open with `npx playwright show-trace`).
        screenshot: 'only-on-failure',
        trace: 'retain-on-failure',
    },
    projects: targets.flatMap((t) => [
        {
            name: `${t.name}-login`,
            testMatch: /login\.setup\.ts/,
            use: { baseURL: t.url },
        },
        {
            name: t.name,
            testMatch: /.*\.spec\.ts/,
            dependencies: [`${t.name}-login`],
            use: { baseURL: t.url, storageState: `.auth/${t.name}.json` },
        },
    ]),
});
