import {
    test,
    expect,
    chooseType,
    choose,
    dialog,
    editor,
    loadedSize,
    newPage,
    openDialog,
    openPage,
    placeholders,
    save,
    savedContent,
} from './support';

// The dialog: listing the registered shortcodes, inserting one at the cursor, editing one that is
// selected, and what ends up in the database on save (editor_plugin.js toSrc).

test.describe('Shortcode dialog', () => {
    test('lists the registered shortcodes; choosing one loads its fields', async ({ page }) => {
        const { id } = await newPage(page, '<p>Plain text</p>');
        await openPage(page, id);
        await editor(page).locator('body p').click();
        expect(await openDialog(page), 'a new shortcode: no data posted').toBe('');

        await expect(dialog(page).locator('.modal-title')).toHaveText('Insert/edit shortcode');
        // Labels: getShortcodeLabel() for the plain class, singular_name for the DataObject.
        const options = dialog(page).locator('select[name="ShortcodeType"] option');
        await expect(options).toHaveText([/Shortcode type/, /Browser year/, /Browser record/]);
        // Nothing to insert before a type is chosen.
        await expect(dialog(page).locator('#Form_ShortcodeForm_action_insert')).toHaveCount(0);

        await chooseType(page, 'Browser record');
        // A DataObject shortcode gets an "id" dropdown of its records, plus its getShortcodeFields().
        await expect(dialog(page).locator('select[name="id"] option')).toContainText(['Alpha record', 'Bravo record']);
        await expect(dialog(page).locator('input[name="caption"]')).toBeVisible();
    });

    test('inserts at the cursor; Save stores the shortcode, not the placeholder', async ({ page }) => {
        const { id, records } = await newPage(page, '<p>Start here</p>');
        await openPage(page, id);
        // Cursor at the end of the paragraph.
        await editor(page).locator('body p').click();
        await page.keyboard.press('End');

        await openDialog(page);
        await chooseType(page, 'Browser record');
        await choose(page, 'id', 'Bravo record');
        await dialog(page).locator('input[name="caption"]').fill('Hi there');
        await dialog(page).locator('#Form_ShortcodeForm_action_insert').click();
        await expect(dialog(page)).toBeHidden();

        const img = placeholders(page);
        await expect(img).toHaveCount(1);
        await expect(img).toHaveAttribute('title', `scb_record id="${records['Bravo record']}" caption="Hi there"`);
        expect(await loadedSize(img)).toEqual({ w: 300, h: 50 });
        // Inserted at the cursor: the existing text is still there, in front of it.
        await expect(editor(page).locator('body p')).toContainText('Start here');

        await save(page, id);
        const content = await savedContent(page, id);
        expect(content).toContain(`[scb_record id="${records['Bravo record']}" caption="Hi there"]`);
        expect(content).toContain('Start here');
        expect(content, 'no placeholder markup is saved').not.toContain('<img');
    });

    test('a selected placeholder reopens the dialog filled in, and Insert replaces it', async ({ page }) => {
        const { records } = await newPage(page, '');
        const alpha = records['Alpha record'];
        const { id } = await newPage(page, `<p>Intro [scb_record id="${alpha}" caption="Old"] outro</p>`);
        await openPage(page, id);

        // Clicking a non-editable placeholder selects it.
        await placeholders(page).click();
        const posted = new URLSearchParams(await openDialog(page));
        // The selection was turned back into a shortcode and parsed (getCurrentEditorSelectionAsParsedShortcodeData).
        expect(Object.fromEntries(posted)).toEqual({ ShortcodeType: 'scb_record', id: String(alpha), caption: 'Old' });
        await expect(dialog(page).locator('select[name="ShortcodeType"]')).toHaveValue('scb_record');
        await expect(dialog(page).locator('select[name="id"]')).toHaveValue(String(alpha));
        await expect(dialog(page).locator('input[name="caption"]')).toHaveValue('Old');

        await dialog(page).locator('input[name="caption"]').fill('New');
        await dialog(page).locator('#Form_ShortcodeForm_action_insert').click();
        await expect(dialog(page)).toBeHidden();

        // Replaced, not added next to it.
        await expect(placeholders(page)).toHaveCount(1);
        await expect(placeholders(page)).toHaveAttribute('title', `scb_record id="${alpha}" caption="New"`);

        await save(page, id);
        const content = await savedContent(page, id);
        expect(content).toContain(`[scb_record id="${alpha}" caption="New"]`);
        expect(content).not.toContain('caption="Old"');
        expect(content).toContain('Intro');
        expect(content).toContain('outro');
    });
});
