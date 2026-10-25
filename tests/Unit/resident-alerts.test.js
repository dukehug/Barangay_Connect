import test from 'node:test';
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import {runInNewContext} from 'node:vm';

const alertsSource = readFileSync(new URL('../../public/resident/js/resident-alerts.js', import.meta.url), 'utf8');
const documentsSource = readFileSync(new URL('../../public/resident/js/documents.js', import.meta.url), 'utf8');

function alertPage(dialog = null) {
    const calls = [];
    const body = {};
    const window = {Swal: {fire: options => { calls.push(options); return Promise.resolve({isConfirmed: false}); }}};
    runInNewContext(alertsSource, {
        window,
        document: {body, querySelector: () => dialog, addEventListener() {}},
    });
    return {window, calls, body};
}

test('notices share the required center position and five-second non-dismissible backdrop', async () => {
    const page = alertPage();
    await page.window.ResidentAlerts.fire({title: 'Saved', text: '<b>plain text</b>'});
    const options = page.calls[0];
    assert.equal(options.position, 'center');
    assert.equal(options.timer, 5000);
    assert.equal(options.timerProgressBar, true);
    assert.equal(options.allowOutsideClick, false);
    assert.equal(options.text, '<b>plain text</b>');
    assert.equal(options.html, undefined);
    assert.equal(options.target, page.body);
});

test('notices inside a native dialog stay in its top layer', async () => {
    const dialog = {};
    const page = alertPage(dialog);
    await page.window.ResidentAlerts.fire({title: 'Wait for upload'});
    assert.equal(page.calls[0].target, dialog);
});

function confirmationForm(result, selector) {
    let listener;
    let submissions = 0;
    let prompts = 0;
    const form = {
        addEventListener: (name, callback) => { listener = callback; },
        requestSubmit: submitter => {
            const event = {submitter, defaultPrevented: false, preventDefault() { this.defaultPrevented = true; }};
            listener(event);
            if (!event.defaultPrevented) submissions++;
        },
    };
    runInNewContext(documentsSource, {
        window: {ResidentAlerts: {fire: async () => { prompts++; return result; }}},
        document: {
            getElementById: () => null,
            querySelector: () => null,
            querySelectorAll: query => query === selector ? [form] : [],
        },
    });
    return {
        submit: () => listener({defaultPrevented: false, preventDefault() {}, submitter: {}}),
        submissions: () => submissions,
        prompts: () => prompts,
    };
}

for (const selector of ['[data-confirm-cancel]', '[data-delete-attachment]']) {
    test(`${selector} only submits once after explicit confirmation`, async () => {
        const form = confirmationForm({isConfirmed: true}, selector);
        await form.submit();
        assert.equal(form.submissions(), 1);
        assert.equal(form.prompts(), 1);
    });
    test(`${selector} timeout or dismissal never submits`, async () => {
        for (const dismiss of ['timer', 'cancel', 'esc']) {
            const form = confirmationForm({isConfirmed: false, dismiss}, selector);
            await form.submit();
            assert.equal(form.submissions(), 0);
        }
    });
}
