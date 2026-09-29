# Page templates implementation plan

Approved: catalog with built-in templates and saving existing pages as reusable templates. Create makes an independent editable page. Populate initial presets after implementing the catalog.

Architecture: TemplateService persists snapshots in tap_templates; built-ins live in a versioned PHP catalog. A transaction clones pages/sections/blocks with fresh IDs. Creation request tokens map to deterministic new page IDs for retry safety. Only presentation settings are captured, never global scripts or credentials. Existing images and external product references remain shared.

- [x] PHP tests against SQLite for independent snapshots, graph cloning, safe retries, rollback, deleted sources and immutable presets; then implement service and catalog.
- [x] Add authenticated API actions for list/save/create/delete and a sandboxed preview endpoint. New schema is additive.
- [x] Add Templates navigation, cards with preview/Create, filters and errors; shared Save as template dialog on both page editors.
- [x] Add four built-in presets: profile, services, course, portfolio. Use existing block types and page-local design.
- [ ] Verify PHP lint, transaction tests, browser workflows, and existing zero-editor regression. Deploy only task-related differences with backup and authenticated smoke tests.

Local verification completed: 90 PHP assertions, zero-editor regression tests, PHP/JS syntax checks and browser workflows passed. Desktop/mobile screenshots inspected. Production files match the pre-change local baseline. Deployment upload was rejected by automatic approval review because this feature requires explicit production deployment approval; no production files or schema changed.
