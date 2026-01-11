<p align="center">
  <img src=".github/logos/logo.svg#gh-light-mode-only" width="400" alt="XetaSuite Logo">
  <img src=".github/logos/logo-dark.svg#gh-dark-mode-only" width="400" alt="XetaSuite Logo">
</p>

<p align="center">
  <strong>Backend API for XetaSuite - Multi-Tenant Facility Management ERP</strong>
</p>

<p align="center">
  <a href="#"><img src="https://img.shields.io/badge/PHP-8.4+-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP 8.4+"></a>
  <a href="#"><img src="https://img.shields.io/badge/Laravel-12.x-FF2D20?style=flat-square&logo=laravel&logoColor=white" alt="Laravel 12"></a>
  <a href="#"><img src="https://img.shields.io/badge/PostgreSQL-18+-336791?style=flat-square&logo=postgresql&logoColor=white" alt="PostgreSQL"></a>
  <a href="#"><img src="https://img.shields.io/github/actions/workflow/status/XetaIO/XetaSuite-Core/tests.yml?style=flat-square" alt="Tests"></a>
  <a href="#"><img src="https://img.shields.io/badge/License-MIT-green?style=flat-square" alt="License MIT"></a>
</p>

---

## 📋 About XetaSuite

**XetaSuite** is a **multi-tenant ERP (Enterprise Resource Planning)** designed for **comprehensive facility, equipment, and inventory management**. This professional solution enables businesses to efficiently manage their sites, zones, materials, stock, and interventions (maintenance, cleaning, incidents).

### 🎯 Who is XetaSuite for?

XetaSuite is ideal for:

| Industry | Use Cases |
|----------|-----------|
| 🏢 **Facility Management** | Building management, workspaces, meeting rooms |
| 🏭 **Manufacturing & Production** | Equipment tracking, preventive maintenance, spare parts management |
| 🏥 **Healthcare & Hospitals** | Medical equipment inventory, equipment traceability, cleaning schedules |
| 🏫 **Education** | School furniture management, IT equipment tracking |
| 🏨 **Hospitality & Catering** | Linen management, kitchen equipment, room maintenance |
| 🏬 **Retail & Commerce** | Multi-store management, product inventory, incident tracking |
| 🏗️ **Construction** | Construction equipment management, tool tracking |
| 🚚 **Logistics & Warehousing** | Storage zone management, stock movement tracking |

---

## ✨ Key Features

### 🏢 Multi-Site Management (Multi-Tenancy)

- **Multiple sites**: Manage several locations from a single interface
- **Headquarters site**: Centralized administration with global access
- **Per-site permissions**: Each user has specific roles/permissions per site
- **Dynamic site switching**: Switch between sites without logging out

### 🗺️ Hierarchical Zone Organization

- **Nested zones**: Unlimited tree structure (Building → Floor → Room)
- **Hierarchical visualization**: Intuitive tree navigation
- **Property inheritance**: Configuration propagated to child zones

### 🔧 Equipment & Material Management

- **Complete records**: Reference, brand, model, serial number, acquisition date
- **Zone assignment**: Precise location for each piece of equipment
- **QR Codes**: Automatic generation for quick identification
- **Full history**: Traceability of all interventions on each material

### 📦 Stock & Inventory Management

- **Items catalog**: Complete catalog with references, descriptions, units
- **Stock movements**: Entries, exits, transfers with full traceability
- **QR Codes**: Automatic generation for quick identification
- **Price history**: Track purchase cost evolution
- **Stock alerts**: Configurable thresholds for reordering

### 🏢 Company Management

- **Unified company model**: Companies can be item providers, maintenance contractors, or both
- **Type-based filtering**: Filter companies by their role (ITEM_PROVIDER, MAINTENANCE_PROVIDER)
- **Headquarters management**: Centralized database managed from HQ site
- **Full traceability**: Track all items and maintenances linked to each company

### 🛠️ Maintenance & Interventions

- **Scheduling**: Preventive maintenance with configurable recurrence
- **Intervention tracking**: Statuses (scheduled, in progress, completed, cancelled)
- **External contractors**: Maintenance company management
- **Costs & budgets**: Track expenses per intervention

### 🧹 Cleaning Management

- **Cleaning schedules**: Configurable frequencies per zone
- **Service tracking**: Validation and history of cleaning sessions
- **Quality control**: Notes and comments on services

### ⚠️ Incident Management

- **Simplified reporting**: Quick reporting with location
- **Priority levels**: Critical, high, medium, low
- **Resolution workflow**: Statuses and team assignment
- **Statistics**: Dashboards and indicators

### 👥 User & Access Management

- **Secure authentication**: Laravel Sanctum (SPA) + Fortify
- **Customizable roles**: Admin, Manager, Operator, etc.
- **Granular permissions**: Fine-grained access control per feature
- **Per-site permissions**: Users can have different roles depending on the site

### 🌐 Internationalization

- **Multi-language**: French and English included
- **User preferences**: Language saved per profile
- **Extensible**: Easy addition of new languages

### 🔔 Notifications

- **Real-time notifications**: Alerts and important events
- **Notification center**: Searchable history
- **Read/unread marking**: Individual and bulk management

### 🔍 Global Search

- **Unified search**: Search across all resources from a single endpoint
- **Multi-type results**: Materials, zones, items, incidents, maintenances, companies, sites
- **Permission-aware**: Results filtered based on user's permissions
- **Site-scoped**: Regular users see only their site's data, HQ users see all

---

## 🏗️ Technical Architecture

### Technology Stack

| Component | Technology | Version |
|-----------|------------|---------|
| **Framework** | Laravel | 12.x |
| **Language** | PHP | 8.4+ |
| **Database** | PostgreSQL | 18+ |
| **Authentication** | Laravel Sanctum + Fortify | 4.x / 1.x |
| **Permissions** | spatie/laravel-permission | 6.x |
| **Testing** | Pest PHP | 4.x |
| **QR Codes** | endroid/qr-code | 6.x |
| **Activity Log** | spatie/laravel-activitylog | 4.x |

### Code Architecture

```
app/
├── Actions/{Domain}/          # Single-responsibility action classes
├── Enums/{Domain}/            # PHP enums with labels
├── Http/
│   ├── Controllers/Api/V1/    # Versioned API controllers
│   ├── Requests/V1/           # Form Requests with validation
│   ├── Resources/V1/          # JSON transformers
│   └── Middleware/            # Custom middleware
├── Models/                    # Eloquent models
├── Observers/                 # Lifecycle hooks
├── Policies/                  # Resource authorization
└── Services/                  # Complex business logic
```

### Data Model

```
Site (tenant)
├── Zone (hierarchical, self-referential)
│   ├── Material (equipment)
│   │   ├── Cleanings
│   │   ├── Incidents
│   │   └── Maintenances
├── Items (stock)
│   ├── ItemMovements
│   └── ItemPrices
└── Users (with per-site roles)

Headquarters (HQ Site)
└── Companies (unified: item providers + maintenance contractors)
    ├── types: [ITEM_PROVIDER, MAINTENANCE_PROVIDER]
    ├── → Items (as supplier)
    └── → Maintenances (as contractor)
```

---

## 🚀 Installation

### Prerequisites

- PHP 8.4 or higher
- Composer 2.x
- PostgreSQL 18 or higher

### Setup

```bash
# Clone the repository
git clone https://github.com/XetaIO/XetaSuite-core.git
cd XetaSuite-core

# Install dependencies
composer install

# Configure environment
cp .env.example .env
php artisan key:generate

# Configure database in .env
# DB_CONNECTION=pgsql
# DB_DATABASE=xetasuite
# ...

# Run migrations and seeders
php artisan migrate --seed

# Start development server
composer run dev
```

### Important Environment Variables

```env
# Application
APP_URL=https://xetasuite.test
SPA_URL=http://localhost:5173

# Database
DB_CONNECTION=pgsql
DB_DATABASE=xetasuite

# Sanctum (SPA)
SANCTUM_STATEFUL_DOMAINS=localhost:5173,xetasuite.test

# Session
SESSION_DRIVER=database
SESSION_DOMAIN=.xetasuite.test

# Demo mode (optional)
DEMO_MODE=false
```

---

## 🧪 Testing

XetaSuite uses **Pest PHP** for testing with comprehensive coverage:

```bash
# Run all tests
php artisan test

# Run tests with filter
php artisan test --filter=CompanyController

# Run tests by directory
php artisan test tests/Feature/Observers/
```

---

## 📖 API Documentation

The API follows REST conventions with versioning (`/api/v1/`). Main endpoints:

| Endpoint | Description |
|----------|-------------|
| `POST /api/v1/auth/login` | Authentication |
| `GET /api/v1/user` | Current user |
| `PATCH /api/v1/user/site` | Switch site |
| `GET /api/v1/sites` | List sites |
| `GET /api/v1/zones` | Current site zones |
| `GET /api/v1/materials` | Materials |
| `GET /api/v1/items` | Items/Stock |
| `GET /api/v1/maintenances` | Maintenances |
| `GET /api/v1/incidents` | Incidents |
| `GET /api/v1/cleanings` | Cleanings |
| `GET /api/v1/companies` | Companies (item providers + contractors, HQ only) |

---

## 🎭 Demo Mode

XetaSuite can be deployed in demonstration mode:

```env
DEMO_MODE=true
```

In demo mode:
- Destructive actions are blocked (deleting sites, users, etc.)
- Database is reset every 6 hours
- Test accounts available: `admin@xetasuite.demo`, `manager@xetasuite.demo`, `user@xetasuite.demo`

---

## 🤝 Contributing

Contributions are welcome! Please review the contribution guide before submitting a Pull Request.

```bash
# Format code before committing
vendor/bin/pint --dirty
```

---

## 📄 License

XetaSuite is open-source software licensed under the [MIT](LICENSE) license.

---

## 🔗 Links

- **React Frontend**: [XetaSuite-React](https://github.com/XetaIO/XetaSuite-React)
- **Issues**: [GitHub Issues](https://github.com/XetaIO/XetaSuite-core/issues)
