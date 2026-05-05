# Small-e.com - Kenyan E-commerce Platform

A modern, feature-rich e-commerce platform built with PHP Slim 4, Twig templates, and Tailwind CSS. Designed specifically for the Kenyan market with local payment integrations, county-based delivery, and Swahili language support.

![Small-e.com Logo](https://img.shields.io/badge/small-e-commerce-blue?style=for-the-badge)
![PHP](https://img.shields.io/badge/PHP-8.1+-777BB4?style=flat-square&logo=php)
![Slim](https://img.shields.io/badge/Slim-4-green?style=flat-square)
![TailwindCSS](https://img.shields.io/badge/TailwindCSS-4-38B2AC?style=flat-square&logo=tailwind-css)

## Features

### Core E-commerce Features
- **Product Management** - Categories, variants, inventory tracking
- **Shopping Cart** - Session-based cart with coupon support
- **User Authentication** - Registration, login, profile management
- **Order Management** - Full order lifecycle with status tracking
- **Payment Processing** - M-Pesa integration with COD fallback
- **Admin Dashboard** - Complete admin panel for store management
- **Product Reviews** - Customer reviews with admin moderation
- **Sales Reports** - Analytics dashboard with charts
- **Newsletter Subscription** - Email marketing integration
- **Order Tracking** - Public order lookup by number and email

### Kenyan-Specific Features
- **County Integration** - All 47 Kenyan counties with sub-counties
- **M-Pesa Payment** - Full STK Push integration with sandbox support
- **KRA Invoice Generation** - Tax-ready invoice generation
- **Local Delivery** - County-based delivery fee calculation
- **Swahili Translation** - Complete Swahili language support
- **WhatsApp Integration** - Floating WhatsApp contact button
- **GDPR Compliance** - Cookie consent banner
- **Cultural Products** - Kenyan artisan and cultural products

### Technical Features
- **Dark Mode** - Complete dark/light theme toggle
- **Responsive Design** - Mobile-first responsive layout
- **Security** - CSRF protection, secure sessions
- **Multi-language** - English/Swahili with easy expansion
- **Analytics** - Built-in site statistics tracking
- **Performance** - Optimized for speed and scalability

## Quick Start

### Prerequisites
- PHP 8.1 or higher
- Composer
- Node.js & npm (for frontend assets)
- SQLite (default) or MySQL/PostgreSQL

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/wanjaswilly/small-ecommerce-template.git
   cd small-ecommerce-template
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node dependencies**
   ```bash
   npm install
   ```

4. **Environment Setup**
   ```bash
   cp .env.example .env
   # Edit .env with your configuration
   ```

5. **Database Setup & Demo Data**
   ```bash
   php slim demo:import
   ```
   This command will:
   - Run all migrations
   - Create demo categories, products, users, orders, reviews, and coupons
   - Seed with realistic Kenyan data

6. **Build Frontend Assets**
   ```bash
   npm run build
   # or for development
   npm run dev
   ```

7. **Start the Development Server**
   ```bash
   php slim serve
   ```
   Visit `http://localhost:8000`

## Demo Data Included

The demo import creates:
- **5 Product Categories** - Electronics, Fashion, Home, Books, Sports
- **25 Kenyan Products** - Authentic products with local pricing
- **5 Users** - Including 1 admin user (admin@example.com / password123)
- **10 Sample Orders** - Various statuses and payment methods
- **20 Product Reviews** - Mixed ratings with approval status
- **3 Coupon Codes** - Percentage and fixed discounts

### Default Admin Account
- Email: `admin@example.com`
- Password: `password123`
- Access admin panel at: `/admin`

## Configuration

### Environment Variables (.env)

```env
# Application
APP_NAME="Small Ecommerce"
APP_URL="http://localhost:8000"
APP_ENVIRONMENT="development"

# Database
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

# M-Pesa Integration
MPESA_CONSUMER_KEY=your_consumer_key
MPESA_CONSUMER_SECRET=your_consumer_secret
MPESA_SHORTCODE=your_shortcode
MPESA_PASSKEY=your_passkey
MPESA_ENVIRONMENT=sandbox

# Settings
WHATSAPP_NUMBER=+254741400006
STORE_EMAIL=store@small-e.com

```

### Payment Integration

#### M-Pesa Setup
1. Register for M-Pesa Daraja API
2. Get consumer key, secret, shortcode, and passkey
3. Update `.env` file with credentials
4. Test with sandbox environment first

#### Cash on Delivery
COD restrictions can be configured per county in the admin settings.

### Branding Customization

#### Colors
Update `tailwind.config.js` to change the primary color scheme:
```js
primary: {
  500: '#your-color', // Main brand color
  // ... other shades
}
```

#### Logo & Store Name
- Update `templates/partials/header.twig` logo section
- Change `APP_NAME` in `.env` file

#### Language Support
Add new languages in `app/Lang/` directory and update the language switcher.

## Usage Guide

### For Customers
1. **Browse Products** - Explore categories and search products
2. **Add to Cart** - Use the shopping cart functionality
3. **Checkout** - Multi-step process with address and payment selection
4. **Track Orders** - Use order number and email for tracking
5. **Write Reviews** - Leave reviews for purchased products

### For Administrators
1. **Dashboard** - Overview of sales, orders, and analytics
2. **Product Management** - Add, edit, delete products and categories
3. **Order Management** - Process orders, update status, generate invoices
4. **Coupon Management** - Create and manage discount codes
5. **Review Moderation** - Approve or reject customer reviews
6. **Reports** - View sales analytics and performance metrics
7. **Settings** - Configure store settings, payments, and delivery

### CLI Commands

```bash
# Development server
php slim serve

# Database operations
php slim migrate          # Run migrations
php slim demo:import      # Full demo data import

# Content management
php slim make:model ModelName -m    # Create model with migration
php slim make:controller ControllerName
php slim make:factory FactoryName   # Create data factory
php slim seed ModelName count       # Seed specific model
```

## API Endpoints

### Public APIs
- `GET /api/counties/{county}/sub-counties` - Get sub-counties for a county
- `GET /api/mpesa/status/{id}` - Check M-Pesa payment status

### Admin APIs
- `POST /newsletter/subscribe` - Newsletter subscription
- Various CRUD endpoints for products, orders, coupons

## Kenyan Localization

### Counties & Regions
- Complete coverage of all 47 Kenyan counties
- Sub-county data for accurate delivery
- County-based delivery fee calculation

### Cultural Products
- Traditional Kenyan crafts and artifacts
- Maasai beaded jewelry
- Acacia wood carvings
- Kikoi and other traditional fabrics

### Payment Methods
- M-Pesa (most popular)
- Cash on Delivery (COD)
- Card payments via Pesapal

### Language Support
- English (default)
- Swahili (complete translation)
- Easy to add more languages

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request


## Acknowledgments

- Built with [Slim Framework](https://www.slimframework.com/)
- Styled with [Tailwind CSS](https://tailwindcss.com/)
- Kenyan county data sourced from official government records
- Icons by [Font Awesome](https://fontawesome.com/)

## Support

- **Documentation**: [Wiki](https://github.com/wanjaswilly/small-ecommerce-template/wiki)
- **Issues**: [GitHub Issues](https://github.com/wanjaswilly/small-ecommerce-template/issues)
- **Discussions**: [GitHub Discussions](https://github.com/wanjaswilly/small-ecommerce-template/discussions)

---

**Made with ❤️ for Kenya** 🇰🇪

Transforming e-commerce in Kenya, one transaction at a time.