# Small Ecommerce Template


A lightweight, reusable **Slim 4 + Twig boilerplate** for small ecommerce company or businesses.

Ideal for fast deployment of small ecommerce sites like:

* single product sites
* single category sites
* single owner business

---

## Features

* Slim 4 routing
* Twig templating
* Tailwind support with Vite
* Clean layout with partials
* Custom 404 & 500 error pages
* session/authentication enabled
* SQLite-ready (with outofthebox config to migrate to mysql or postgre)
* Fast and easy to clone for new projects
* A **`slim` CLI command** for scaffolding pages, models, migrations, factories, and seeding

---

## Getting Started

### Prerequisites

* PHP 8.0+
* Composer
* Node.js 16+
* SQLite (optional, for blog/DB features)

### Installation

1. Clone the repository:

```bash
git clone https://github.com/wanjaswilly/small-ecomerce-template.git ./company-site
cd company-site
```

2. Install dependencies:

```bash
composer install
npm install && npm run build
```

3. Configure environment:

```bash
cp .env.example .env
# Edit .env with your settings
```

4. Configure CSS & JS:
   Check the generated files in `public/build` and update `templates/layout.twig` accordingly.

### Development

```bash
php slim serve
```

Visit: [http://localhost:8000](http://localhost:8000)

---

## Project Structure

```
company-site/
├── app/                  # Application core
│   ├── Controllers       # Request handlers
│   ├── Exceptions        # Request exceptions to be raised
│   ├── Helpers           # Utilities
│   ├── Middlewares       # HTTP middleware
│   ├── Models            # Eloquent models
│   └── Services          # Services classes
├── config/               # Configuration files
│   ├── app.php           # Main config
│   ├── database.php      # Database config
│   └── projects.php      # Example data config
├── database/             # DB files
│   ├── migrations/       # Migration files
│   ├── factories/        # Model factories
│   ├── seeders.php       # Seeder registry
│   └── database.sqlite   # SQLite DB (auto-created)
├── public/               # Web root
│   ├── build/            # Compiled assets
│   └── images/           # Site images
├── resources/            # Frontend assets
│   ├── css/              # Custom styles
│   └── js/               # JavaScript
├── routes/               # Route definitions
│   └── web.php           # Main routes
├── templates/            # Twig templates
│   ├── layout.twig       # Base template
│   ├── admin/            # admin view components
│   ├── partials/         # Reusable components
│   ├── pages/            # user pages templates
│   └── errors/           # Error pages
├── .env.example          # Environment template
├── bootstrap.php         # bootstrapp the application
├── composer.json         # PHP dependencies
├── package.json          # JS dependencies
└── slim                  # Custom CLI tool
```

---

## Slim CLI

The **`slim` CLI tool** makes it easy to scaffold pages, partials, models, migrations, factories, and seeders.

### Available Commands

| Command                                      | Description                                      |
| -------------------------------------------- | ------------------------------------------------ |
| `php slim make:page about`                   | Create a new route + Twig page                   |
| `php slim remove:page about`                 | Remove page + route                              |
| `php slim make:partial footer`               | Create a new partial in `templates/partials/`    |
| `php slim make:controller User`              | Create a new controller (`UserController.php`)   |
| `php slim make:model Post`                   | Create a new model (`app/Models/Post.php`)       |
| `php slim make:model Post -m`                | Create a model **and** a matching migration      |
| `php slim make:migration create_posts_table` | Create a blank migration file                    |
| `php slim migrate`                           | Run all pending migrations                       |
| `php slim make:factory Post`                 | Create a factory for the `Post` model            |
| `php slim seed Post 10`                      | Seed 10 fake `Post` records (via factory)        |
| `php slim serve`                             | Start local dev server (`http://localhost:8000`) |

---

## Database Support

* **Models** use [Eloquent ORM](https://laravel.com/docs/eloquent).
* **Migrations** are plain PHP classes with `up()` and `down()` methods.
* **Factories** generate fake model data using [Faker](https://fakerphp.github.io/).
* **Seeders** let you quickly populate tables with demo/test data.

---

## Error Pages

* `templates/errors/404.twig` -> Not Found errors
* `templates/errors/500.twig` -> General server errors

These are rendered automatically via middleware defined in `bootstrap.php`.

---

## License

All Rights Reserved © 2026 Wilson Wanja
