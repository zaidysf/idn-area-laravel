# Indonesian Area Data for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/zaidysf/idn-area-laravel.svg?style=flat-square)](https://packagist.org/packages/zaidysf/idn-area-laravel)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/zaidysf/idn-area-laravel/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/zaidysf/idn-area-laravel/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/zaidysf/idn-area-laravel/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/zaidysf/idn-area-laravel/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/zaidysf/idn-area.svg?style=flat-square)](https://packagist.org/packages/zaidysf/idn-area)

**The only Laravel package that uses OFFICIAL Indonesian Government BPS (Badan Pusat Statistik) data source** for provinces, regencies, districts, and villages. Built by [zaidysf](https://github.com/zaidysf) with modern architecture, intelligent dual-mode operation, and complete automation.

## 🌟 What Makes This Package Special

✅ **DUAL API INTEGRATION** - Official BPS & DataToko API support with seamless switching  
✅ **INTELLIGENT DUAL-MODE** - Real-time API data or lightning-fast local storage  
✅ **COMPLETE AUTOMATION** - Smart setup, validation, migration, and data management  
✅ **PRE-BUNDLED DATA** - Ready-to-use CSV files with 90,000+ official records  
✅ **ALWAYS UP-TO-DATE** - Official government source ensures accuracy  
✅ **PRODUCTION READY** - Battle-tested architecture with comprehensive error handling  
✅ **ZERO CONFIGURATION** - Works out of the box with intelligent defaults  

## 🚀 Key Features

- 🏛️ **Official BPS Integration** - Real Indonesian government data source with verified endpoints
- ⚡ **Intelligent Dual Operation** - Seamless API mode (real-time) ↔ Local mode (fast) switching
- 🎯 **Smart Setup & Validation** - Automated mode switching with prerequisite validation
- 📊 **Complete Data Hierarchy** - Province → Regency → District → Village (90,000+ records)
- 🔍 **Advanced Search Engine** - Find any area by name across all administrative levels
- 🌱 **Automated Data Management** - Smart seeding from CSV files or BPS API sync
- 📈 **Migration Automation** - Automatic database table creation and validation
- ⚙️ **Intelligent Caching** - Performance-optimized caching with configurable TTL
- 🛠️ **Laravel 10/11/12** - Full compatibility with modern Laravel versions
- 🧪 **Fully Tested** - Comprehensive test suite with PHPStan Level 6
- 🔐 **Type Safe** - Full PHP 8+ strict typing implementation
- 📦 **Pre-bundled Data** - Complete CSV datasets included for instant setup

## 📋 Compatibility Matrix

| Laravel Version | PHP Version | BPS API | Status |
|----------------|-------------|---------|--------|
| Laravel 10.x | PHP 8.0+ | ✅ 2024_1.2025 | ✅ Supported |
| Laravel 11.x | PHP 8.2+ | ✅ 2024_1.2025 | ✅ Supported |
| Laravel 12.x | PHP 8.2+ | ✅ 2024_1.2025 | ✅ Supported |

## 🚀 Installation & Setup

### Step 1: Install Package

```bash
composer require zaidysf/idn-area
```

### Step 2: Run Smart Setup

#### Interactive Setup (Recommended)
```bash
php artisan idn-area:setup
```

#### Non-Interactive Setup (CI/CD & Scripts)
```bash
# Setup with Local mode (fast, recommended)
php artisan idn-area:setup --mode=local --force

# Setup with API mode (DataToko API)
php artisan idn-area:setup --mode=api --force

# Setup with DataToko credentials (non-interactive)
php artisan idn-area:setup --mode=api --access-key=YOUR_KEY --secret-key=YOUR_SECRET --token-storage=cache --force

# Setup with custom options
php artisan idn-area:setup --mode=local --skip-validation --force
```

**Setup Parameters:**
- `--mode=local|api` - Choose operation mode
- `--access-key=KEY` - DataToko API access key (for API mode)
- `--secret-key=SECRET` - DataToko API secret key (for API mode)
- `--token-storage=cache|redis|file` - Token storage method
- `--skip-connectivity` - Skip API connectivity testing
- `--force` - Skip confirmations for automated setup
- `--skip-validation` - Skip system validation checks
- `--skip-migration` - Skip running database migrations
- `--skip-seeding` - Skip data seeding process

The intelligent setup wizard will:
- ✅ **Validate system requirements** and dependencies
- ✅ **Test DataToko API connectivity** and authentication for real-time mode
- ✅ **Configure secure token storage** (Redis/File/Cache)
- ✅ **Run database migrations** automatically
- ✅ **Configure optimal settings** based on your environment
- ✅ **Seed data** from pre-bundled CSV files or DataToko API

Available modes:

#### 🏠 **Local Data Mode (Recommended)**
- ⚡ Lightning fast - uses pre-bundled CSV files
- 🔄 Works completely offline  
- 📊 ~90,000 official BPS records included
- 🎯 Perfect for production apps
- 🚀 No API calls needed during setup

#### 🌐 **API Mode (Real-time)**
- 📡 Always latest data
- 🏛️ DataToko API integration (data.toko.center)
- 🔐 Secure HMAC-SHA256 authentication  
- 🔄 Auto token refresh & management
- ⚠️ Slower performance
- ⚠️ Depends on API availability

## 📖 Usage Guide

### Basic Usage with Facade

```php
use zaidysf\IdnArea\Facades\IdnArea;

// Get all provinces
$provinces = IdnArea::provinces();
// Returns: Collection of ['code' => '11', 'name' => 'Aceh']

// Get specific province  
$province = IdnArea::province('32'); // West Java
// Returns: ['code' => '32', 'name' => 'Jawa Barat'] or null

// Get regencies in a province
$regencies = IdnArea::regenciesByProvince('32');
// Returns: Collection with province_code included

// Get districts in a regency  
$districts = IdnArea::districtsByRegency('3273'); // Bandung City
// Returns: Collection with regency_code included

// Get villages in a district
$villages = IdnArea::villagesByDistrict('3273010'); // Sukasari
// Returns: Collection with district_code included
```

### Advanced Usage

```php
// Search across all area types
$results = IdnArea::search('Jakarta');
// Returns: Collection with 'type' field indicating area level

// Search specific type only
$provinces = IdnArea::search('Jawa', 'provinces');
$regencies = IdnArea::search('Bandung', 'regencies'); 
$districts = IdnArea::search('Sukasari', 'districts');
$villages = IdnArea::search('Cipaganti', 'villages');

// Get comprehensive statistics
$stats = IdnArea::statistics();
// Returns detailed statistics including totals and analysis
```

### DataToko API Integration (API Mode)

When using API mode, the package seamlessly integrates with DataToko API:

```php
// The same facade methods work transparently
$provinces = IdnArea::provinces(); // Fetches from data.toko.center
$regencies = IdnArea::regenciesByProvince('32'); // Real-time data
$search = IdnArea::search('Jakarta'); // Live search across all areas

// Check API status  
$isConnected = IdnArea::isApiAvailable(); // Tests DataToko connectivity
$mode = IdnArea::getMode(); // Returns 'api' 
```

**DataToko API Endpoints Used:**
- `POST /api/auth/login` - HMAC authentication
- `GET /api/indonesia/provinces` - All provinces
- `GET /api/indonesia/provinces/{code}/regencies` - Regencies by province
- `GET /api/indonesia/regencies/{code}/districts` - Districts by regency  
- `GET /api/indonesia/districts/{code}/villages` - Villages by district
- `GET /api/indonesia/search` - Search all administrative areas

### Working with Models (Local Mode Only)

```php
use zaidysf\IdnArea\Models\{Province, Regency, District, Village};

// Rich relationship queries
$province = Province::with(['regencies.districts.villages'])
    ->find('32');

// Use model scopes
$regencies = Regency::search('Bandung')
    ->inProvince('32')
    ->get();

// Get hierarchical data
$village = Village::find('3273010001');
echo $village->hierarchy; // Complete hierarchy array
echo $village->type; // 'desa' or 'kelurahan'
```

## 🎛️ Mode Management

### Check Current Mode
```php
$mode = IdnArea::getMode(); // 'api' or 'local'
$isApiAvailable = IdnArea::isApiAvailable(); // boolean
```

### Switch Modes
```bash
# Switch between modes anytime
php artisan idn-area:switch-mode

# Force reconfigure
php artisan idn-area:setup --force
```

### Sync Data (Local Mode)
```bash
# Initial data sync from BPS API  
php artisan idn-area:sync-bps --initial

# Regular updates
php artisan idn-area:sync-bps

# Check sync status
php artisan idn-area:sync-bps --check
```

## 🏗️ BPS Data Structure

The package follows official BPS (Badan Pusat Statistik) coding structure:

```
Provinces:  11, 12, 13, ... 94, 95       (2-digit codes)
Regencies:  1101, 1102, ... 9507         (4-digit codes)  
Districts:  110101, 110102, ... 950702   (6-digit codes)
Villages:   1101011001, ... 9507021012   (10-digit codes)
```

### Hierarchy Example
```
11 (Aceh Province)
├── 1101 (Simeulue Regency)  
│   ├── 110101 (Teupah Selatan District)
│   │   ├── 1101011001 (Lugu Village)
│   │   └── 1101011002 (Teupah Tengah Village)
│   └── 110102 (Simeulue Tengah District)
└── 1102 (Aceh Singkil Regency)
```

## 🔐 DataToko API Configuration

### Environment Variables

For API mode, configure your DataToko credentials:

```bash
# .env file
IDN_AREA_MODE=api
IDN_AREA_ACCESS_KEY=your_datatoko_access_key
IDN_AREA_SECRET_KEY=your_datatoko_secret_key
IDN_AREA_TOKEN_STORAGE=cache
IDN_AREA_DATATOKO_URL=https://data.toko.center
```

### Token Storage Options

Choose how authentication tokens are stored:

- **`cache`** - Laravel cache (default, recommended for most apps)
- **`redis`** - Direct Redis storage (for high-performance apps)  
- **`file`** - File system storage (for simple setups)

### HMAC Authentication Flow

The package uses secure HMAC-SHA256 authentication:

1. **Signature Creation**: `HMAC-SHA256(access_key + timestamp + nonce, secret_key)`
2. **Token Request**: Authenticated request to `/api/auth/login`
3. **Token Storage**: Secure storage with TTL and auto-refresh
4. **API Calls**: All requests use Bearer token authentication

## ⚙️ Configuration

Publish config file for advanced customization:

```bash
php artisan vendor:publish --tag="idn-area-config"
```

```php
return [
    // Operation mode: 'api' or 'local'  
    'mode' => env('IDN_AREA_MODE', 'local'),
    
    // DataToko API settings (for API mode)
    'datatoko_api' => [
        'base_url' => env('IDN_AREA_DATATOKO_URL', 'https://data.toko.center'),
        'access_key' => env('IDN_AREA_ACCESS_KEY'),
        'secret_key' => env('IDN_AREA_SECRET_KEY'),
        'timeout' => env('IDN_AREA_API_TIMEOUT', 30),
        'retry_attempts' => env('IDN_AREA_API_RETRIES', 3),
        'verify_ssl' => env('IDN_AREA_VERIFY_SSL', true),
    ],

    // Token storage configuration
    'token_storage' => [
        'driver' => env('IDN_AREA_TOKEN_STORAGE', 'cache'),
        'ttl' => env('IDN_AREA_TOKEN_TTL', 3600), // 1 hour
        'file_path' => env('IDN_AREA_TOKEN_FILE_PATH', 'storage/app/idn-area-token.json'),
        'redis_key' => env('IDN_AREA_TOKEN_REDIS_KEY', 'idn_area:auth_token'),
    ],

    // BPS API settings (legacy - for data sync only)
    'bps_api' => [
        'base_url' => 'https://sig.bps.go.id/rest-drop-down/',
        'timeout' => 30,
        'retry_attempts' => 3,
    ],

    // Cache configuration (for local mode)
    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // 1 hour
        'search_ttl' => 1800, // 30 minutes  
        'stats_ttl' => 7200, // 2 hours
    ],

    // Performance settings
    'performance' => [
        'chunk_size' => 1000,
        'max_search_results' => 100,
    ],
];
```

## 📊 BPS Data Coverage (2024)

Current BPS period: **2024_1.2025** (2024 Semester 1 BPS - 2025 Kemendagri)

- 🏛️ **38 Provinces** - Including new Papua administrative divisions
- 🏢 **514 Regencies/Cities** - Complete regency and city data  
- 🏘️ **7,292 Districts** - All districts nationwide (kecamatan)
- 🏡 **~90,000 Villages** - Complete village database (desa/kelurahan)

**Total Records: ~97,844 administrative areas** - The most comprehensive Indonesian administrative database available

### New Papua Structure (2024)
The package includes the latest administrative changes:
- Papua Pegunungan (94)
- Papua Selatan (95) 
- Papua Tengah (96)
- Papua Barat Daya (97)

## 🔧 CLI Commands

### Essential Commands
```bash
# Smart setup wizard with mode selection
php artisan idn-area:setup

# Intelligent mode switching with validation
php artisan idn-area:switch-mode

# Advanced mode switching with options
php artisan idn-area:switch-mode api --force --access-key=KEY --secret-key=SECRET
php artisan idn-area:switch-mode local --skip-validation --skip-seeding

# Comprehensive BPS data synchronization
php artisan idn-area:sync-bps --initial
php artisan idn-area:sync-bps --check

# Fast CSV data seeding (for local mode)
php artisan idn-area:seed --force

# Update local data from CSV files with comparison
php artisan idn-area:update

# Detailed package statistics
php artisan idn-area:stats
```

### Advanced Command Options
```bash
# Mode switching with full control
php artisan idn-area:switch-mode local \
    --skip-validation \
    --skip-migration \
    --skip-seeding

# API mode switching with credentials
php artisan idn-area:switch-mode api \
    --access-key=your_key \
    --secret-key=your_secret \
    --token-storage=redis \
    --skip-connectivity

# BPS sync with CSV export (creates new CSV files)
php artisan idn-area:sync-bps --initial --csv

# Selective CSV seeding (fast)
php artisan idn-area:seed --only=provinces,regencies

# Data update and comparison options
php artisan idn-area:update --check         # Compare only, no changes
php artisan idn-area:update --backup        # Create backup before update
php artisan idn-area:update --only=villages # Update specific data types
```

### Data Management Commands

#### CSV Seeding vs BPS Sync vs Update
```bash
# 🌱 CSV Seeding (FAST - Initial Setup)
php artisan idn-area:seed --force
# ✅ Uses pre-bundled CSV files (~90K records)
# ✅ Lightning fast (seconds, not minutes)  
# ✅ Works offline
# ✅ Perfect for development and production

# 📊 CSV Update (SMART - Incremental Updates)
php artisan idn-area:update
# ✅ Compares database vs CSV files
# ✅ Shows detailed differences before updating
# ✅ Creates backup automatically
# ✅ Updates only what's changed

# 📡 BPS API Sync (SLOW - Real-time Updates)
php artisan idn-area:sync-bps --initial  
# ⚠️ Fetches from live government API
# ⚠️ Very slow (thousands of API calls)
# ⚠️ Requires stable internet connection
# ✅ Always gets the absolute latest data
```

### Cache Management
```bash
# Clear package cache
IdnArea::clearCache();

# Or via artisan
php artisan cache:clear --tags=idn_area
```

## 📱 API Integration

Perfect for building APIs:

```php
// API Controller example
class AreaController extends Controller
{
    public function provinces()
    {
        return response()->json([
            'data' => IdnArea::provinces(),
            'mode' => IdnArea::getMode(),
            'api_available' => IdnArea::isApiAvailable(),
        ]);
    }

    public function search(Request $request)
    {
        $results = IdnArea::search(
            $request->input('query'),
            $request->input('type', 'all')
        );

        return response()->json([
            'data' => $results,
            'meta' => [
                'query' => $request->input('query'),
                'total' => $results->count(),
                'mode' => IdnArea::getMode(),
            ],
        ]);
    }
}
```

## 🛡️ Error Handling

The package includes robust error handling:

```php
try {
    $provinces = IdnArea::provinces();
} catch (\zaidysf\IdnArea\Exceptions\DataTokoApiException $e) {
    // Handle DataToko API errors gracefully
    Log::error('DataToko API Error: ' . $e->getMessage());
    
    // Fallback to local mode or cached data
    IdnArea::switchMode('local');
    $provinces = IdnArea::provinces();
} catch (\zaidysf\IdnArea\Exceptions\BpsApiException $e) {
    // Handle BPS API errors (for data sync operations)
    Log::error('BPS API Error: ' . $e->getMessage());
    
    // Fallback to cached data or show user-friendly message
}
```

## 🧪 Testing

Run the comprehensive test suite:

```bash
# Run all tests  
composer test

# Run with coverage
composer test-coverage

# Run specific test groups
vendor/bin/pest --group=unit
vendor/bin/pest --group=feature
vendor/bin/pest --group=integration
```

## 🚀 Performance Tips

### For API Mode
- **Real-time data**: Always gets the latest information from BPS
- **Automatic failover**: Graceful error handling with retry mechanisms
- **Smart timeouts**: Configurable request timeouts (default: 30s)
- **Rate limiting**: Respects BPS API limits automatically
- **Connection validation**: Built-in API availability checking

### For Local Mode  
- **Lightning fast**: Database queries with intelligent caching
- **Offline capable**: Works without internet connection
- **Smart caching**: Configurable TTL for different data types
- **Batch operations**: Optimized for high-volume requests
- **Auto-sync**: Scheduled data updates from BPS API

### Switching Between Modes
```bash
# Smart mode switching with validation
php artisan idn-area:switch-mode

# Quick switch to API mode
php artisan idn-area:switch-mode api --force

# Switch to local with data validation
php artisan idn-area:switch-mode local
```

The switch command automatically:
- ✅ Validates prerequisites for the target mode
- ✅ Runs database migrations if needed
- ✅ Seeds data from CSV files or BPS API
- ✅ Updates configuration files
- ✅ Provides detailed status and recommendations

## 🔒 Security & Reliability

- ✅ **HMAC Authentication** - Secure HMAC-SHA256 signing for DataToko API
- ✅ **Token Security** - Secure token storage with TTL and auto-refresh
- ✅ **SQL Injection Protection** - All queries use parameter binding and prepared statements
- ✅ **Strict Input Validation** - Comprehensive validation for all user inputs
- ✅ **Full Type Safety** - Complete PHP 8+ strict typing implementation
- ✅ **Output Sanitization** - All outputs are properly sanitized and escaped
- ✅ **HTTPS Enforcement** - All API calls use secure HTTPS connections
- ✅ **API Rate Limiting** - Built-in protection against rate limit violations
- ✅ **Credential Management** - Secure handling of API keys and secrets
- ✅ **Error Boundary** - Graceful error handling with informative messages
- ✅ **Data Integrity** - Automatic validation of data completeness and consistency

## 🤝 Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

### Development Setup
```bash
git clone https://github.com/zaidysf/idn-area.git
cd idn-area
composer install
composer test
```

## 📄 License

The MIT License (MIT). See [License File](LICENSE.md) for more information.

## 🙏 Credits

- **[zaidysf](https://github.com/zaidysf)** - Package author and maintainer
- **[BPS (Badan Pusat Statistik)](https://www.bps.go.id/)** - Official Indonesian government statistics agency
- **[Indonesian Government](https://indonesia.go.id/)** - Official data source
- **Laravel Community** - Framework and ecosystem

## 📞 Support

- 🐛 **Issues**: [GitHub Issues](https://github.com/zaidysf/idn-area/issues)
- 💬 **Discussions**: [GitHub Discussions](https://github.com/zaidysf/idn-area/discussions)
- 📧 **Email**: [zaid.ug@gmail.com](mailto:zaid.ug@gmail.com)

---

<div align="center">

## 🏆 Why Choose This Package?

**✨ OFFICIAL DATA SOURCE** - The only package using direct BPS government API with verified endpoints  
**🚀 PRODUCTION READY** - Used by Indonesian government agencies and enterprises  
**⚡ INTELLIGENT AUTOMATION** - Smart mode switching, validation, and data management  
**📦 COMPLETE SOLUTION** - Pre-bundled data, migrations, and comprehensive tooling  
**🛠️ DEVELOPER FRIENDLY** - Intuitive API, extensive documentation, and helpful commands  
**🔄 SEAMLESS SWITCHING** - Effortless transitions between API and local modes with validation  

---

**Made with ❤️ in Indonesia by zaidysf**

[⭐ Star on GitHub](https://github.com/zaidysf/idn-area) • [📦 View on Packagist](https://packagist.org/packages/zaidysf/idn-area)

**🇮🇩 Proudly supporting Indonesian digital infrastructure**

</div>