# Indonesian Area Data for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/zaidysf/idn-area-laravel.svg?style=flat-square)](https://packagist.org/packages/zaidysf/idn-area-laravel)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/zaidysf/idn-area-laravel/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/zaidysf/idn-area-laravel/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/zaidysf/idn-area-laravel/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/zaidysf/idn-area-laravel/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/zaidysf/idn-area-laravel.svg?style=flat-square)](https://packagist.org/packages/zaidysf/idn-area-laravel)

Clean and fast Indonesian administrative area data package for Laravel. Get provinces, regencies, districts, and villages with curated official data.

## ✨ Features

- 🚀 **Fast Local Mode** - No API calls, works offline
- 🌐 **API Mode** - Real-time data via DataToko service  
- 🧹 **Clean Data** - Curated and validated from official sources
- 🔍 **Search & Filter** - Advanced search capabilities
- 📦 **Easy Setup** - Simple artisan commands
- ✅ **Laravel 10/11/12** - Full compatibility
- 🧪 **100% Tested** - 186 passing tests

## 📊 Data Coverage

| Type | Count | Source |
|------|-------|--------|
| Provinces | 38 | Official Indonesian Government |
| Regencies | 514+ | Curated from BPS Data |
| Districts | 7,292+ | Updated Regularly |
| Villages | 84,345+ | Complete Coverage |

## 🚀 Installation

```bash
composer require zaidysf/idn-area-laravel
```

## ⚡ Quick Setup

```bash
# Setup the package (choose local mode for speed)
php artisan idn-area:setup --mode=local

# Or use API mode for real-time data
php artisan idn-area:setup --mode=api
```

## 💡 Usage

### 🏛️ Provinces

```php
use zaidysf\IdnArea\Facades\IdnArea;

// Get all provinces
$provinces = IdnArea::getProvinces();
// Returns: [['code' => '11', 'name' => 'ACEH'], ...]

// Find specific province
$province = IdnArea::findProvince('11');
// Returns: ['code' => '11', 'name' => 'ACEH']

// Search provinces by name
$results = IdnArea::searchProvinces('jawa');
// Returns: [['code' => '32', 'name' => 'JAWA BARAT'], ...]

// Check if province exists
$exists = IdnArea::hasProvince('11'); // true
```

### 🏢 Regencies

```php
// Get all regencies in a province
$regencies = IdnArea::getRegencies('11'); // Aceh regencies
// Returns: [['code' => '1101', 'province_code' => '11', 'name' => 'SIMEULUE'], ...]

// Find specific regency
$regency = IdnArea::findRegency('1101');
// Returns: ['code' => '1101', 'province_code' => '11', 'name' => 'SIMEULUE']

// Search regencies in specific province
$results = IdnArea::searchRegencies('bandung', '32');
// Returns regencies containing 'bandung' in West Java

// Search regencies across all provinces
$allResults = IdnArea::searchRegencies('bandung');

// Check if regency exists
$exists = IdnArea::hasRegency('1101'); // true
```

### 🏘️ Districts

```php
// Get all districts in a regency
$districts = IdnArea::getDistricts('1101'); // Simeulue districts  
// Returns: [['code' => '110101', 'regency_code' => '1101', 'name' => 'TEUPAH SELATAN'], ...]

// Find specific district
$district = IdnArea::findDistrict('110101');
// Returns: ['code' => '110101', 'regency_code' => '1101', 'name' => 'TEUPAH SELATAN']

// Search districts in specific regency
$results = IdnArea::searchDistricts('selatan', '1101');

// Search districts across all regencies
$allResults = IdnArea::searchDistricts('selatan');

// Check if district exists
$exists = IdnArea::hasDistrict('110101'); // true
```

### 🏡 Villages

```php
// Get all villages in a district
$villages = IdnArea::getVillages('110101'); // Teupah Selatan villages
// Returns: [['code' => '1101012001', 'district_code' => '110101', 'name' => 'LATIUNG'], ...]

// Find specific village
$village = IdnArea::findVillage('1101012001');
// Returns: ['code' => '1101012001', 'district_code' => '110101', 'name' => 'LATIUNG']

// Search villages in specific district
$results = IdnArea::searchVillages('latiung', '110101');

// Search villages across all districts
$allResults = IdnArea::searchVillages('latiung');

// Check if village exists
$exists = IdnArea::hasVillage('1101012001'); // true
```

### 🔍 Advanced Search & Utilities

```php
// Get complete hierarchy (province with all children)
$hierarchy = IdnArea::getHierarchy('11'); 
// Returns province with regencies, districts, and villages

// Get multiple areas by codes
$areas = IdnArea::getMultipleByCode(['11', '12', '13']);
// Returns array of provinces with specified codes

// Get statistics
$stats = IdnArea::getStatistics();
// Returns: ['provinces' => 38, 'regencies' => 514, 'districts' => 7292, 'villages' => 84345]

// Get all data (use with caution - large dataset)
$allData = IdnArea::getAllData();
```

### Using Models Directly

```php
use zaidysf\IdnArea\Models\Province;
use zaidysf\IdnArea\Models\Regency;

// Eloquent relationships
$province = Province::with('regencies')->find('11');
$regencyCount = $province->regencies->count();

// Search with scopes
$searchResults = Province::search('jawa')->get();
```

## 🔧 Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag="idn-area-config"
```

### Local Mode (Recommended)
```php
// config/idn-area.php
'mode' => 'local', // Fast, offline, reliable
```

### API Mode 
```php
// config/idn-area.php
'mode' => 'api',
'datatoko_api' => [
    'base_url' => env('IDN_AREA_DATATOKO_URL'),
    'access_key' => env('IDN_AREA_ACCESS_KEY'),
    'secret_key' => env('IDN_AREA_SECRET_KEY'),
],
```

Add to your `.env`:
```env
IDN_AREA_MODE=local
IDN_AREA_ACCESS_KEY=your_access_key
IDN_AREA_SECRET_KEY=your_secret_key
```

## 🎛️ Artisan Commands

```bash
# Initial setup
php artisan idn-area:setup

# Switch between modes
php artisan idn-area:switch-mode api
php artisan idn-area:switch-mode local

# View package info
php artisan idn-area

# View statistics  
php artisan idn-area:stats

# Cache management
php artisan idn-area:cache warm
php artisan idn-area:cache clear
```

## 🧪 Testing

```bash
composer test
```

## 📈 Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## 🤝 Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## 🔒 Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## 📄 Credits

- [zaidysf](https://github.com/zaidysf)
- [All Contributors](../../contributors)

## 📝 License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## 🇮🇩 About Indonesian Data

This package provides official Indonesian administrative area data sourced from government databases. The data is curated and regularly updated to ensure accuracy and completeness.

**Data Sources:**
- Badan Pusat Statistik (BPS) - Indonesian Central Statistics Agency
- Official Government Administrative Records  
- Validated and cleaned for consistency

**Use Cases:**
- E-commerce shipping forms
- Government applications
- Data analysis and reporting
- Location-based services
- Administrative systems