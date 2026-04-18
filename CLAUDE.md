# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Cerb is a 24-year-old PHP/MySQL helpdesk and workflow automation platform. It uses a custom MVC framework called **Devblocks** (not Laravel/Symfony). The codebase is mature and follows consistent patterns throughout.

## Common Commands

```bash
# Clear template and cache files
composer cache-clear

# Run platform tests
composer test

# Run tests manually with PHPUnit
vendor/bin/phpunit --do-not-cache-result --bootstrap tests/bootstrap.platform.php -c tests/phpunit.cerb.platform.xml

# Start local development environment
cd install/docker && docker compose up

# Connect to MySQL console (password: s3cr3t)
docker exec -it cerb-mysql-1 mysql -u root -p cerb
```

## Architecture

### Directory Structure
- `api/` - Application-level code (Application.class.php, Extension.class.php)
- `libs/devblocks/` - The Devblocks framework core
- `features/` - Built-in plugins/features (cerberusweb.core is the main one)
- `plugins/` - Third-party plugins
- `install/` - Installation scripts, Docker config, SQL schema
- `storage/` - Runtime data, cache, compiled templates

### Plugin/Feature Structure
Each plugin follows this structure:
```
features/plugin.name/
├── plugin.xml          # Manifest: class loaders, extension points, dependencies
├── strings.xml         # i18n translations
├── src/
│   ├── App.php         # Extension implementations
│   ├── dao/            # DAO classes
│   └── profiles/       # Profile page handlers
└── templates/          # Smarty templates
```

### Key Framework Services
Access via `DevblocksPlatform::services()`:
- `->database()` - Database connection (supports master/reader)
- `->cache()` - Caching (disk, memcached, redis)
- `->template()` - Smarty templating
- `->validation()` - Field validation
- `->event()` - Event dispatcher
- `->automation()` - Workflow automation engine

### DAO Pattern
All Data Access Objects extend `Cerb_ORMHelper`. Standard structure:

```php
class DAO_RecordType extends Cerb_ORMHelper {
    const ID = 'id';
    const NAME = 'name';
    // Field constants...

    static function getFields()      // Validation schema
    static function create($fields)  // Insert
    static function update($ids, $fields) // Update with events
    static function get($id)         // Fetch by ID
    static function delete($ids)     // Delete with cleanup
    static function search(...)      // Advanced search
}
```

Associated classes for each record type:
- `Model_RecordType` - Data model
- `SearchFields_RecordType` - Search field definitions
- `View_RecordType` - Worklist view
- `Context_RecordType` - Record type context (permissions, cards, etc.)

### Naming Conventions
- DAOs: `DAO_{RecordType}` (e.g., `DAO_KnowledgeSource`)
- Models: `Model_{RecordType}`
- Contexts: `Context_{RecordType}` with `::ID` constant for context string
- Search: `SearchFields_{RecordType}`
- Views: `View_{RecordType}`
- Controllers: `Controller_{Name}`
- Profile sections: `PageSection_Profiles{RecordType}`

### Context System
Records are identified by context strings (e.g., `cerb.contexts.knowledge.source`). The context class defines:
- Permissions (isReadableByActor, isWriteableByActor, isDeletableByActor)
- Profile URL generation
- Token labels/values for placeholders
- Peek popup rendering

### Extension Points
Plugins declare extensions in `plugin.xml`:
- `devblocks.context` - Record types
- `cerberusweb.page` - UI pages
- `cerberusweb.ui.page.section` - Page sections
- `cerb.automation.api_command` - Automation commands
- `cerberusweb.cron` - Scheduled jobs

### Database Operations
- Use `$db->ExecuteMaster()` for writes
- Use `$db->QueryReader()` for reads (goes to replica if configured)
- Parameterized queries via `sprintf()` with `self::qstr()` for escaping
- Batch updates in chunks of 100 for events

### Templates
Uses Smarty 4.x. Templates stored in `templates/` subdirectories:
- `records/types/{record_type}/view.tpl` - Worklist view
- `records/types/{record_type}/peek_edit.tpl` - Edit popup

### Code Generation
The SDK at `install/extras/sdk/devblocks-dao.php` generates boilerplate for new record types. Define table schema and it outputs DAO, Model, SearchFields, View, Context classes and templates.

## Key Patterns

### Creating a New Record Type
1. Add table schema in migration patch (`features/cerberusweb.core/patches/`)
2. Create DAO class with standard methods
3. Create Context class implementing `IDevblocksContextProfile`, `IDevblocksContextPeek`
4. Register in `plugin.xml` under `devblocks.context` extension point
5. Add profile section class
6. Create templates for view and peek_edit

### Form Handling
Profile actions use pattern:
```php
private function _profileAction_savePeekJson() {
    // Validate HTTP method
    // Get form data via DevblocksPlatform::importGPC()
    // Validate with DAO::validate()
    // Check permissions with onBeforeUpdateByActor()
    // Create/update record
    // Return JSON response
}
```

### Events and Deltas
Updates trigger events automatically:
- `CerberusContexts::checkpointChanges()` before update
- `DevblocksPlatform::markContextChanged()` after update
- Event: `dao.{table_name}.update`
