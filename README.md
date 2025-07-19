# Anime-Kun Symfony 7 Migration

This is the modern Symfony 7 rewrite of the Anime-Kun anime/manga database website, migrating from the legacy PHP 5.6 codebase.

## Features

- **Modern Symfony 7** framework with PHP 8.1+ support
- **Comprehensive anime/manga database** with search and filtering
- **User review system** with rating and critique functionality
- **User collections** (anime/manga lists with status tracking)
- **Admin panel** for content management
- **API endpoints** for AJAX functionality
- **Caching system** for performance optimization
- **SEO-friendly URLs** for better search engine visibility

## Tech Stack

- **Backend**: Symfony 7.1, PHP 8.1+
- **Database**: MySQL 8.0 with Doctrine ORM
- **Frontend**: Bootstrap 5, Twig templates
- **Cache**: Symfony Cache component (Redis/Filesystem)
- **Assets**: Webpack Encore (optional)
- **Authentication**: Symfony Security component

## Requirements

- PHP 8.1 or higher
- Composer 2.x
- MySQL 8.0+ (or MariaDB 10.5+)
- Node.js 18+ (for asset compilation)
- Web server (Apache/Nginx)

## Installation

### 1. Clone the project
```bash
git clone <repository-url> anime-kun-symfony
cd anime-kun-symfony
```

### 2. Install PHP dependencies
```bash
composer install
```

### 3. Configure environment
```bash
cp .env ..env.local
```

Edit `.env.local` with your database credentials:
```env
DATABASE_URL="mysql://username:password@127.0.0.1:3306/animekunnet?serverVersion=8.0&charset=utf8mb4"
APP_SECRET="your-secret-key-here"
```

### 4. Create database and run migrations
```bash
# Create database
php bin/console doctrine:database:create

# Generate and run migrations
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate

# Load sample data (optional)
php bin/console doctrine:fixtures:load
```

### 5. Set up file permissions
```bash
chmod +x bin/console
mkdir -p var/cache var/log public/uploads
chmod -R 775 var/ public/uploads/
```

### 6. Install frontend dependencies (optional)
```bash
npm install
npm run build
```

### 7. Start the development server
```bash
# Symfony CLI (recommended)
symfony server:start

# Or PHP built-in server
php -S localhost:8000 -t public/
```

## Migration from Legacy System

### Database Migration

1. **Export legacy data**:
```bash
mysqldump -u username -p animekunnet > legacy_backup.sql
```

2. **Create migration scripts** (examples in `migrations/` folder):
   - `MigrateAnimesData.php` - Convert ak_animes table
   - `MigrateMangasData.php` - Convert ak_mangas table  
   - `MigrateUsersData.php` - Convert user data from SMF
   - `MigrateCritiquesData.php` - Convert ak_critique table

3. **Run data migration**:
```bash
php bin/console doctrine:migrations:execute --up migration_class_name
```

### File Migration

1. **Copy media files**:
```bash
# Anime images
cp -r /old-site/ak8prod/anime_img/* public/uploads/anime/

# Manga images  
cp -r /old-site/ak8prod/manga_img/* public/uploads/manga/

# Screenshots
cp -r /old-site/ak8prod/screenshots/* public/uploads/screenshots/

# User avatars
cp -r /old-site/ak8prod/avatars/* public/uploads/avatars/
```

2. **Set up URL redirects** (in web server config):
```apache
# Apache .htaccess redirects for SEO
RewriteRule ^anime\.php\?id=([0-9]+)$ /animes/$1 [R=301,L]
RewriteRule ^manga\.php\?id=([0-9]+)$ /mangas/$1 [R=301,L]
```

## Development

### Project Structure
```
anime-kun-symfony/
├── config/                 # Symfony configuration
├── migrations/             # Database migrations
├── public/                 # Web root
│   ├── uploads/           # User-uploaded content
│   └── index.php         # Front controller
├── src/
│   ├── Controller/        # HTTP controllers
│   ├── Entity/           # Doctrine entities
│   ├── Repository/       # Database repositories
│   ├── Service/          # Business logic services
│   └── Security/         # Authentication components
├── templates/            # Twig templates
└── var/                 # Cache and logs
```

### Key Components

#### Entities
- `Anime` - Anime information and relationships
- `Manga` - Manga information and relationships  
- `User` - User accounts and authentication
- `Critique` - User reviews and ratings
- `Tag` - Content categorization
- `UserAnimeList` - User's anime collection status

#### Services
- `AnimeService` - Anime business logic and caching
- `MangaService` - Manga business logic and caching
- `CritiqueService` - Review system management
- `RatingCalculationService` - Rating algorithms
- `SearchService` - Search functionality

#### Controllers
- `HomeController` - Homepage and stats
- `AnimeController` - Anime browsing and details
- `MangaController` - Manga browsing and details
- `CritiqueController` - Review system
- `UserController` - User profiles and lists

### Database Schema

The new schema improves upon the legacy structure:

- **Better relationships** using Doctrine associations
- **Proper constraints** and foreign keys
- **UTF-8 support** throughout
- **Indexed fields** for better performance
- **Normalized data** to reduce redundancy

### Caching Strategy

- **Homepage data**: 1 hour cache
- **Anime/Manga details**: 30 minutes cache  
- **Search results**: 15 minutes cache
- **User-specific data**: No cache or short TTL
- **Static assets**: Browser caching via headers

## API Endpoints

The application provides REST API endpoints:

```
GET  /api/animes           # List animes with filters
GET  /api/animes/{id}      # Get anime details
GET  /api/mangas          # List mangas with filters  
GET  /api/mangas/{id}     # Get manga details
POST /api/critiques       # Create new review
GET  /api/search          # Global search
```

## Performance Optimizations

1. **Database indexing** on frequently queried fields
2. **Query optimization** using Doctrine query builder
3. **Caching layers** for expensive operations
4. **Image optimization** and lazy loading
5. **CDN integration** ready for static assets
6. **Database connection pooling** in production

## Security Features

- **CSRF protection** on all forms
- **SQL injection prevention** via Doctrine ORM
- **XSS protection** via Twig auto-escaping
- **Rate limiting** on API endpoints
- **Input validation** using Symfony Validator
- **Secure authentication** with password hashing

## Production Deployment

### Server Requirements
- PHP 8.1+ with required extensions
- MySQL 8.0+ or MariaDB 10.5+
- Web server (Apache/Nginx) with mod_rewrite
- Redis (recommended for caching)
- SSL certificate for HTTPS

### Environment Configuration
```env
APP_ENV=prod
APP_DEBUG=false
DATABASE_URL="mysql://user:pass@localhost:3306/animekunnet?serverVersion=8.0"
REDIS_URL="redis://localhost:6379"
```

### Deployment Steps
```bash
# Install dependencies
composer install --no-dev --optimize-autoloader

# Clear and warm cache
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod

# Run database migrations
php bin/console doctrine:migrations:migrate --no-interaction

# Set permissions
chown -R www-data:www-data var/ public/uploads/
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Write tests for new functionality
4. Submit a pull request

## License

This project is proprietary software for the Anime-Kun community.

## Support

For issues and questions:
- Check the [documentation](docs/)
- Create an issue on GitHub
- Contact the development team

---

**Migration Status**: 🚧 In Progress
- ✅ Core framework setup
- ✅ Database entities and repositories  
- ✅ Basic controllers and routing
- ✅ Template system setup
- 🔄 Authentication system (in progress)
- ⏳ Admin panel (pending)
- ⏳ Data migration scripts (pending)
- ⏳ Production deployment (pending)
