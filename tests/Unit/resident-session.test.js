import test from 'node:test';
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import {runInNewContext} from 'node:vm';

const source = readFileSync(new URL('../../public/resident/js/activity-data.js', import.meta.url), 'utf8');

function residentPage(authenticated = true) {
    const listeners = {};
    const style = {};
    let reloads = 0;
    runInNewContext(source, {
        document: {
            body: {classList: {contains: () => authenticated}},
            documentElement: {style},
            querySelectorAll: () => [],
            querySelector: () => null,
        },
        window: {
            addEventListener: (event, listener) => { listeners[event] = listener; },
            location: {reload: () => { reloads++; }},
        },
    });
    return {listeners, style, reloadCount: () => reloads};
}

test('history snapshots hide resident content and revalidate on restoration', () => {
    const page = residentPage();
    page.listeners.pagehide({persisted: true});
    assert.equal(page.style.visibility, 'hidden');
    page.listeners.pageshow({persisted: true});
    assert.equal(page.reloadCount(), 1);
});

test('normal navigation does not hide content or trigger a reload loop', () => {
    const page = residentPage();
    page.listeners.pageshow({persisted: false});
    page.listeners.pagehide({persisted: false});
    assert.equal(page.style.visibility, undefined);
    assert.equal(page.reloadCount(), 0);
});

test('public login and registration pages are not affected', () => {
    assert.deepEqual(residentPage(false).listeners, {});
});
