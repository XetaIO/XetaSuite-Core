# XetaSuite Core - Project Instructions

> **Last updated**: January 2026

## Architecture Overview

XetaSuite is a **multi-tenant ERP backend** (Laravel 12, PHP 8.2+) serving a React SPA via Sanctum stateful API authentication. Multi-tenancy is site-based: users have roles/permissions per site via `spatie/laravel-permission` with teams.

### Tech Stack
- **PHP** 8.2+ (supports 8.2, 8.3, 8.4)
- **Laravel Framework** 12.x
- **Laravel Sanctum** 4.x (stateful SPA authentication)
- **Laravel Fortify** 1.x (authentication backend)
- **Spatie Permission** 6.x (roles & permissions with teams)
- **Pest** 3.x/4.x (testing framework)
- **PostgreSQL** 16/17/18 (database)

### Domain Hierarchy
```
Site (tenant) → Zone (nested, self-referential) → Material → Cleanings/Incidents/Maintenances
     ↓
   Items ← Company (types: item_provider, maintenance_provider)
     ↓
  ItemMovements / ItemPrices
```

### Key Concepts
- **Headquarters Site**: Central admin site (`is_headquarters = true`) - manages Companies globally
- **Regular Sites**: Operational sites with their own zones, materials, items
- **Company Types**: Unified model with `types` array containing `ITEM_PROVIDER` and/or `MAINTENANCE_PROVIDER`
- **Team Permissions**: Roles scoped to sites via `site_id`; use `setPermissionsTeamId($site->id)` before role operations

## Sanctum SPA Authentication

XetaSuite uses **Sanctum stateful authentication** for the React SPA (cookies, not tokens).

### Configuration Flow
1. **SPA calls** `GET /sanctum/csrf-cookie` → Laravel sets `XSRF-TOKEN` cookie
2. **SPA includes** `X-XSRF-TOKEN` header (auto-handled by Axios `withCredentials: true`)
3. **SPA calls** `POST /auth/login` (Fortify) → Laravel creates session cookie
4. **All subsequent API calls** use session cookie for authentication

### Key Configuration Files
- `config/sanctum.php` → `stateful` domains: `localhost:5173`, `xetasuite.test`
- `config/cors.php` → `supports_credentials: true`, `allowed_origins` includes SPA URL
- `config/session.php` → `driver: database`, `same_site: lax`
- `bootstrap/app.php` → `$middleware->statefulApi()` enables Sanctum middleware

### React SPA Requirements
```typescript
// Axios instance must use:
axios.defaults.withCredentials = true;
axios.defaults.withXSRFToken = true;

// Before login/logout, always call:
await axios.get('/sanctum/csrf-cookie');
```

## Site-Scoped Permissions (Team Flow)

XetaSuite uses `spatie/laravel-permission` with **teams enabled** (`config/permission.php` → `team_foreign_key: 'site_id'`).

### Middleware Chain (order matters)
```
SetCurrentSite → SetCurrentPermissionsAndRoles → Controller
```

1. **SetCurrentSite** - Reads `user.current_site_id`, caches `is_on_headquarters` in session
2. **SetCurrentPermissionsAndRoles** - Calls `setPermissionsTeamId(siteId)`, unsets cached relations

### Assigning Roles (Always Scope to Site)
```php
// CRITICAL: Set team context BEFORE any role/permission operation
setPermissionsTeamId($site->id);
$user->assignRole($role);

// Roles are stored with site_id in model_has_roles pivot table
```

### Checking Permissions in Policies
```php
// Permissions are automatically scoped to current site via middleware
public function view(User $user, Company $company): bool
{
    return $user->can('company.view');  // Checks permission for current site
}
```

### HQ-Only Access Pattern
```php
// In Policy - use global helper
public function before(User $user, string $ability): ?bool
{
    if (! isOnHeadquarters()) return false;  // Blocks access from regular sites
    return null;  // Continue to specific ability check
}
```

## Namespace & Structure

All application code uses `XetaSuite\` namespace (PSR-4: `app/` → `XetaSuite\`).

```
app/
├── Actions/{Domain}/          # Single-purpose action classes (Create*, Update*, Delete*)
├── Enums/{Domain}/            # Backed PHP enums with label() method
├── Http/
│   ├── Controllers/Api/V1/   # API v1 controllers
│   ├── Requests/V1/{Domain}/ # Form Requests with authorize() using policies
│   ├── Resources/V1/{Domain}/ # API Resources (Detail vs simple variants)
│   └── Middleware/           # SetLocale, SetCurrentSite, SetCurrentPermissionsAndRoles
├── Models/Presenters/        # Traits for computed attributes (avatar, full_name, level)
├── Observers/                # Model lifecycle hooks (deletion protection, name preservation)
├── Policies/                 # Authorization with before() for HQ-only resources
└── Services/                 # Query builders, business logic validation
```

## Critical Patterns

### 1. Action Classes over Fat Controllers
```php
// Controllers delegate to single-purpose actions
public function store(StoreCompanyRequest $request, CreateCompany $action): CompanyDetailResource
{
    return new CompanyDetailResource($action->handle($request->user(), $request->validated()));
}
```

### 2. Observer-Based Data Integrity
FKs use `nullOnDelete()` but observers preserve names BEFORE deletion:
```php
// CompanyObserver::deleting - preserves company_name in items/item_movements/item_prices
public function deleting(Company $company): void {
    Item::where('company_id', $company->id)->update(['company_name' => $company->name]);
}
```
- Use `deleting`/`forceDeleting` events (BEFORE DB nullifies FK), not `deleted`
- Return `false` from `deleting()` to block deletion (e.g., `SiteObserver` blocks if zones exist)
- Observers are attached via `#[ObservedBy([CompanyObserver::class])]` attribute on models

### 3. Count Caching via `xetaio/xetaravel-counts`
```php
use Xetaio\Counts\Concerns\HasCounts;

protected static array $countsConfig = [
    'company' => 'item_count',  // Creates Item → Company.item_count sync
];
```
**Never test count caching directly** - it's package functionality.

### 4. HQ-Only Resources (Companies)
Policies use `before()` to restrict access:
```php
public function before(User $user, string $ability): ?bool {
    if (! isOnHeadquarters()) return false;  // Global helper from app/helpers.php
    return null;
}
```

### 5. Factory State Methods
Use semantic state methods, not raw attributes:
```php
Item::factory()
    ->forSite($site)
    ->fromCompany($company)
    ->createdBy($user)
    ->create();
```

## Database

- **PostgreSQL only** (16, 17, 18) - no MySQL/MariaDB support
- Partial unique indexes: use `DB::statement()` for PostgreSQL-specific SQL
- FK constraints: `restrictOnDelete()` for parent records, `nullOnDelete()` for optional refs with `*_name` backup field

## Testing

### Test Helpers (tests/Pest.php)
```php
$user = createUserOnHeadquarters($headquarters, $role);  // HQ user with role
$user = createUserOnRegularSite($site, $role);           // Regular site user
```

### Run Tests
```bash
php artisan test --filter=CompanyController  # Specific pattern
php artisan test tests/Feature/Observers/     # Directory
```

## Commands

```bash
composer run dev          # Server + queue + Vite concurrently
composer run test         # Run all tests with config:clear
vendor/bin/pint --dirty   # Format changed files before finalizing
php artisan test --filter=ClassName  # Run specific tests
```

## Key Packages Reference

| Package | Version | Purpose |
|---------|---------|---------|
| `laravel/framework` | ^12.0 | Core framework |
| `laravel/sanctum` | ^4.0 | SPA authentication (cookies) |
| `laravel/fortify` | 1.x | Auth backend (login, register, 2FA) |
| `spatie/laravel-permission` | ^6.23 | Roles & permissions with teams |
| `spatie/laravel-activitylog` | ^4.10 | Activity logging |
| `spatie/laravel-backup` | ^9.3 | Database backups |
| `xetaio/xetaravel-counts` | ^2.0 | Count caching |
| `endroid/qr-code` | ^6.0 | QR code generation |
| `pestphp/pest` | ^3.0\|^4.1 | Testing framework |

---

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4.16
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- laravel/sanctum (SANCTUM) - v4
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- tailwindcss (TAILWINDCSS) - v4

## Conventions
- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to existing directory structure - don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.


=== boost rules ===

## Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan
- Use the `list-artisan-commands` tool when you need to call an Artisan command to double check the available parameters.

## URLs
- Whenever you share a project URL with the user you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain / IP, and port.

## Tinker / Debugging
- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation specific for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The 'search-docs' tool is perfect for all Laravel related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel-ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries - package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax
- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit"
3. Quoted Phrases (Exact Position) - query="infinite scroll" - Words must be adjacent and in that order
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit"
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms


=== php rules ===

## PHP

- Always use curly braces for control structures, even if it has one line.

### Constructors
- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters.

### Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments
- Prefer PHPDoc blocks over comments. Never use comments within the code itself unless there is something _very_ complex going on.

## PHPDoc Blocks
- Add useful array shape type definitions for arrays when appropriate.

## Enums
- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.


=== herd rules ===

## Laravel Herd

- The application is served by Laravel Herd and will be available at: https?://[kebab-case-project-dir].test. Use the `get-absolute-url` tool to generate URLs for the user to ensure valid URLs.
- You must not run any commands to make the site available via HTTP(s). It is _always_ available through Laravel Herd.


=== tests rules ===

## Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test` with a specific filename or filter.


=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation
- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources
- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing
- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.


=== laravel/v12 rules ===

## Laravel 12

- Use the `search-docs` tool to get version specific documentation.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

### Laravel 12 Structure
- No middleware files in `app/Http/Middleware/`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- **No app\Console\Kernel.php** - use `bootstrap/app.php` or `routes/console.php` for console configuration.
- **Commands auto-register** - files in `app/Console/Commands/` are automatically available and do not require manual registration.

### Database
- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 11 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models
- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.


=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.


=== pest/core rules ===

## Pest
### Testing
- If you need to verify a feature is working, write or update a Unit / Feature test.

### Pest Tests
- All tests must be written using Pest. Use `php artisan make:test --pest {name}`.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files - these are core to the application.
- Tests should test all of the happy paths, failure paths, and weird paths.
- Tests live in the `tests/Feature` and `tests/Unit` directories.
- Pest tests look and behave like this:
<code-snippet name="Basic Pest Test Example" lang="php">
it('is true', function () {
    expect(true)->toBeTrue();
});
</code-snippet>

### Running Tests
- Run the minimal number of tests using an appropriate filter before finalizing code edits.
- To run all tests: `php artisan test`.
- To run all tests in a file: `php artisan test tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --filter=testName` (recommended after making a change to a related file).
- When the tests relating to your changes are passing, ask the user if they would like to run the entire test suite to ensure everything is still passing.

### Pest Assertions
- When asserting status codes on a response, use the specific method like `assertForbidden` and `assertNotFound` instead of using `assertStatus(403)` or similar, e.g.:
<code-snippet name="Pest Example Asserting postJson Response" lang="php">
it('returns all', function () {
    $response = $this->postJson('/api/docs', []);

    $response->assertSuccessful();
});
</code-snippet>

### Mocking
- Mocking can be very helpful when appropriate.
- When mocking, you can use the `Pest\Laravel\mock` Pest function, but always import it via `use function Pest\Laravel\mock;` before using it. Alternatively, you can use `$this->mock()` if existing tests do.
- You can also create partial mocks using the same import or self method.

### Datasets
- Use datasets in Pest to simplify tests which have a lot of duplicated data. This is often the case when testing validation rules, so consider going with this solution when writing tests for validation rules.

<code-snippet name="Pest Dataset Example" lang="php">
it('has emails', function (string $email) {
    expect($email)->not->toBeEmpty();
})->with([
    'james' => 'james@laravel.com',
    'taylor' => 'taylor@laravel.com',
]);
</code-snippet>


=== pest/v4 rules ===

## Pest 4

- Pest v4 is a huge upgrade to Pest and offers: browser testing, smoke testing, visual regression testing, test sharding, and faster type coverage.
- Browser testing is incredibly powerful and useful for this project.
- Browser tests should live in `tests/Browser/`.
- Use the `search-docs` tool for detailed guidance on utilizing these features.

### Browser Testing
- You can use Laravel features like `Event::fake()`, `assertAuthenticated()`, and model factories within Pest v4 browser tests, as well as `RefreshDatabase` (when needed) to ensure a clean state for each test.
- Interact with the page (click, type, scroll, select, submit, drag-and-drop, touch gestures, etc.) when appropriate to complete the test.
- If requested, test on multiple browsers (Chrome, Firefox, Safari).
- If requested, test on different devices and viewports (like iPhone 14 Pro, tablets, or custom breakpoints).
- Switch color schemes (light/dark mode) when appropriate.
- Take screenshots or pause tests for debugging when appropriate.

### Example Tests

<code-snippet name="Pest Browser Test Example" lang="php">
it('may reset the password', function () {
    Notification::fake();

    $this->actingAs(User::factory()->create());

    $page = visit('/sign-in'); // Visit on a real browser...

    $page->assertSee('Sign In')
        ->assertNoJavascriptErrors() // or ->assertNoConsoleLogs()
        ->click('Forgot Password?')
        ->fill('email', 'nuno@laravel.com')
        ->click('Send Reset Link')
        ->assertSee('We have emailed your password reset link!')

    Notification::assertSent(ResetPassword::class);
});
</code-snippet>

<code-snippet name="Pest Smoke Testing Example" lang="php">
$pages = visit(['/', '/about', '/contact']);

$pages->assertNoJavascriptErrors()->assertNoConsoleLogs();
</code-snippet>


=== tailwindcss/core rules ===

## Tailwind Core

- Use Tailwind CSS classes to style HTML, check and use existing tailwind conventions within the project before writing your own.
- Offer to extract repeated patterns into components that match the project's conventions (i.e. Blade, JSX, Vue, etc..)
- Think through class placement, order, priority, and defaults - remove redundant classes, add classes to parent or child carefully to limit repetition, group elements logically
- You can use the `search-docs` tool to get exact examples from the official documentation when needed.

### Spacing
- When listing items, use gap utilities for spacing, don't use margins.

    <code-snippet name="Valid Flex Gap Spacing Example" lang="html">
        <div class="flex gap-8">
            <div>Superior</div>
            <div>Michigan</div>
            <div>Erie</div>
        </div>
    </code-snippet>


### Dark Mode
- If existing pages and components support dark mode, new pages and components must support dark mode in a similar way, typically using `dark:`.


=== tailwindcss/v4 rules ===

## Tailwind 4

- Always use Tailwind CSS v4 - do not use the deprecated utilities.
- `corePlugins` is not supported in Tailwind v4.
- In Tailwind v4, configuration is CSS-first using the `@theme` directive — no separate `tailwind.config.js` file is needed.
<code-snippet name="Extending Theme in CSS" lang="css">
@theme {
  --color-brand: oklch(0.72 0.11 178);
}
</code-snippet>

- In Tailwind v4, you import Tailwind using a regular CSS `@import` statement, not using the `@tailwind` directives used in v3:

<code-snippet name="Tailwind v4 Import Tailwind Diff" lang="diff">
   - @tailwind base;
   - @tailwind components;
   - @tailwind utilities;
   + @import "tailwindcss";
</code-snippet>


### Replaced Utilities
- Tailwind v4 removed deprecated utilities. Do not use the deprecated option - use the replacement.
- Opacity values are still numeric.

| Deprecated |	Replacement |
|------------+--------------|
| bg-opacity-* | bg-black/* |
| text-opacity-* | text-black/* |
| border-opacity-* | border-black/* |
| divide-opacity-* | divide-black/* |
| ring-opacity-* | ring-black/* |
| placeholder-opacity-* | placeholder-black/* |
| flex-shrink-* | shrink-* |
| flex-grow-* | grow-* |
| overflow-ellipsis | text-ellipsis |
| decoration-slice | box-decoration-slice |
| decoration-clone | box-decoration-clone |


=== laravel/fortify rules ===

## Laravel Fortify

Fortify is a headless authentication backend that provides authentication routes and controllers for Laravel applications.

**Before implementing any authentication features, use the `search-docs` tool to get the latest docs for that specific feature.**

### Configuration & Setup
- Check `config/fortify.php` to see what's enabled. Use `search-docs` for detailed information on specific features.
- Enable features by adding them to the `'features' => []` array: `Features::registration()`, `Features::resetPasswords()`, etc.
- To see the all Fortify registered routes, use the `list-routes` tool with the `only_vendor: true` and `action: "Fortify"` parameters.
- Fortify includes view routes by default (login, register). Set `'views' => false` in the configuration file to disable them if you're handling views yourself.

### Customization
- Views can be customized in `FortifyServiceProvider`'s `boot()` method using `Fortify::loginView()`, `Fortify::registerView()`, etc.
- Customize authentication logic with `Fortify::authenticateUsing()` for custom user retrieval / validation.
- Actions in `app/Actions/Fortify/` handle business logic (user creation, password reset, etc.). They're fully customizable, so you can modify them to change feature behavior.

## Available Features
- `Features::registration()` for user registration.
- `Features::emailVerification()` to verify new user emails.
- `Features::twoFactorAuthentication()` for 2FA with QR codes and recovery codes.
  - Add options: `['confirmPassword' => true, 'confirm' => true]` to require password confirmation and OTP confirmation before enabling 2FA.
- `Features::updateProfileInformation()` to let users update their profile.
- `Features::updatePasswords()` to let users change their passwords.
- `Features::resetPasswords()` for password reset via email.
</laravel-boost-guidelines>
