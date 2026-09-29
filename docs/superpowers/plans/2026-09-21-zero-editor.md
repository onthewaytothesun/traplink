# Zero editor implementation plan

Approved in conversation: layer editor for block type 51, with desktop/mobile layouts, history and backward compatibility.

Architecture: standalone dialog opened from both Alpine forms; JSON model in options.zero; PHP renderer shared by public pages and previews. No dependencies or database migration. Existing uncommitted changes must be preserved.

- [x] Model and regression tests: assets/zero-model.js, tests/zero-model.test.cjs. Deep-clone input; desktop/mobile geometry; layers with stable IDs; history; reorder/duplicate/lock/delete. Run node --test tests/zero-model.test.cjs.
- [x] Safe PHP renderer: includes/zero-render.php, tests/zero-render.php. Escape text and attributes, validate URLs/colors/numbers; hidden layers omitted; responsive boards; retain legacy HTML through existing renderer. Run php tests/zero-render.php.
- [x] Dialog: assets/zero-editor.js and assets/zero-editor.css. Layer list, canvas, properties, drag/resize, snapping, zoom, upload, keyboard history. Draft applies only on explicit Apply; Cancel discards changes.
- [x] Integrate launch control in both admin forms; render structured zero in page.php and both previews. Keep raw HTML preview escaped.
- [x] Verify PHP lint, JS syntax, regression tests and browser workflow including reopen, mobile layout, undo, cancellation and rendered output. Do not deploy unrelated working-tree changes.

Verification: 5 Node model tests, 12 PHP renderer checks and the Chrome browser workflow passed. PHP lint and JS syntax checks passed. No database or production deployment was performed.
