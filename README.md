# ShiftEnd - Restaurant Reporting & Shift Closure Tool

A comprehensive web application for restaurant owners and teams to manage daily reports, track analytics, and handle shift closures.

## 🚀 Features

- **User Authentication** with role-based access (Owner, Manager, Employee)
- **Daily Report Input** with customer counts, payment breakdowns, and notes
- **Analytics Dashboard** with charts and insights
- **Multi-location Support** for restaurant chains
- **Team Management** with role assignments
- **Shift Notes** for internal communication
- **Export Functionality** (PDF, CSV, Excel)
- **Notifications & Reminders**

## 🛠️ Tech Stack

- **Backend**: Laravel 12 with PHP 8.2+
- **Frontend**: Vue.js 3 + Vuetify 3
- **Database**: PostgreSQL (Supabase)
- **Authentication**: Laravel Fortify
- **Charts**: ApexCharts
- **Build Tool**: Vite

## 📋 Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js 18+ and npm
- PostgreSQL database (Supabase)

## 🚀 Installation

### 1. Clone the Repository

```bash
git clone <repository-url>
cd backend
```

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install
```

### 3. Environment Setup

Create a `.env` file in the root directory:

```env
APP_NAME=ShiftEnd
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=pgsql
DB_HOST=db.otkpnnztkudgkrychfuj.supabase.co
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=your_supabase_password

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

SUPABASE_URL=https://otkpnnztkudgkrychfuj.supabase.co
SUPABASE_API_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImJvamNrdWJpeHFoZXJvaGVxY2NvIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTEzMTIzNTEsImV4cCI6MjA2Njg4ODM1MX0.KTc9WQ1Ez6XEw3k5p6Jvk3vB91kdQirBkDq9kdMilQM
```

**Important**: Replace `your_supabase_password` with your actual Supabase database password.

### 4. Generate Application Key

```bash
php artisan key:generate
```

### 5. Run Database Migrations

```bash
php artisan migrate
```

### 6. Seed the Database

```bash
php artisan db:seed
```

This will create demo users:
- **Owner**: owner@shiftend.com / password
- **Manager**: manager@shiftend.com / password
- **Employee**: employee@shiftend.com / password

### 7. Build Frontend Assets

```bash
npm run build
```

### 8. Start the Development Server

```bash
# Start Laravel server
php artisan serve

# In another terminal, start Vite for frontend development
npm run dev
```

## 📊 Database Structure

### Core Tables

- **users** - User accounts with roles
- **restaurants** - Restaurant information
- **restaurant_user** - Many-to-many relationship with roles
- **reports** - Daily shift reports
- **shift_notes** - Internal communication notes
- **activity_logs** - User action tracking
- **reminders** - Custom reminders

### Key Relationships

- Users can belong to multiple restaurants with different roles
- Each report is linked to a specific restaurant and user
- Shift notes are attached to reports
- Activity logs track all user actions

## 🔐 Authentication & Roles

### User Roles

- **Owner**: Full access to all restaurants they own
- **Manager**: Access to assigned restaurants, can manage employees
- **Employee**: Can create reports and add notes

### Role Permissions

- **Owners** can:
  - Create and manage restaurants
  - Invite team members
  - View all analytics and reports
  - Export data

- **Managers** can:
  - Create and edit reports
  - Manage shift notes
  - View restaurant analytics
  - Manage employees

- **Employees** can:
  - Create daily reports
  - Add shift notes
  - View their own reports

## 🎯 API Endpoints

The application provides RESTful API endpoints for:

- User authentication and management
- Restaurant CRUD operations
- Report creation and retrieval
- Analytics data
- Team management

## 📱 Frontend Structure

### Vue.js Components

- **App.vue** - Main application layout with sidebar
- **Dashboard.vue** - Analytics and overview
- **AddReport.vue** - Daily report form
- **History.vue** - Report history and calendar view
- **Team.vue** - Team management
- **Settings.vue** - User and app settings
- **Login.vue** - Authentication

### Key Features

- Responsive design with Vuetify 3
- Dark/light mode support
- Real-time data updates
- Interactive charts with ApexCharts
- Form validation
- Toast notifications

## 🚀 Deployment

### Production Setup

1. Set `APP_ENV=production` in `.env`
2. Set `APP_DEBUG=false` in `.env`
3. Configure your production database
4. Run `npm run build` for production assets
5. Set up proper web server (Nginx/Apache)
6. Configure SSL certificates

### Environment Variables

Ensure all required environment variables are set:

- Database connection details
- Supabase credentials
- Mail configuration (for notifications)
- Queue configuration (for background jobs)

## 🧪 Testing

```bash
# Run PHP tests
php artisan test

# Run frontend tests (if configured)
npm run test
```

## 📈 Analytics Features

- Customer count trends
- Revenue analysis
- Payment method breakdown
- Peak day detection
- Staff performance metrics
- Waste tracking

## 🔔 Notifications

- Daily report reminders
- Missing report alerts
- Team invitation notifications
- Shift note updates

## 📝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## 📄 License

This project is licensed under the MIT License.

## 🆘 Support

For support and questions:
- Create an issue in the repository
- Check the documentation
- Contact the development team

## 🔄 Updates

To update the application:

```bash
# Pull latest changes
git pull origin main

# Update dependencies
composer install
npm install

# Run migrations
php artisan migrate

# Rebuild assets
npm run build
```

---

**ShiftEnd** - Streamlining restaurant operations, one shift at a time.
