# DataToko Indonesian Area API - Postman Collection

This directory contains a comprehensive Postman collection for the **data.toko.center** Indonesian Area Data API that complies with your Laravel package implementation.

## 📁 Files Included

- **`DataToko-Indonesian-Area-API.postman_collection.json`** - Complete API collection
- **`DataToko-Environment.postman_environment.json`** - Environment template with variables
- **`README.md`** - This documentation file

## 🚀 Quick Setup

### 1. Import into Postman

1. Open Postman
2. Click **Import** button
3. Drag and drop both `.json` files or browse to select them
4. Collection and environment will be imported

### 2. Configure Environment Variables

After importing, select the "DataToko Indonesian Area API Environment" and set:

| Variable | Value | Description |
|----------|--------|-------------|
| `access_key` | Your access key | **REQUIRED** - Get from DataToko |
| `secret_key` | Your secret key | **REQUIRED** - Get from DataToko |
| `base_url` | `https://data.toko.center` | API base URL (pre-filled) |

> **🔑 Get API Keys:** Contact [DataToko](https://data.toko.center) for enterprise-grade API access

### 3. Authenticate

1. Select the collection environment
2. Run the **"Authentication > Login"** request first
3. Auth token will be automatically stored for subsequent requests
4. Token expires after 1 hour - re-run login if needed

## 📊 API Endpoints Overview

### 🔐 Authentication
- **POST** `/api/auth/login` - HMAC SHA256 authentication

### 🏛️ Provinces (38 total)
- **GET** `/api/indonesia/provinces` - All provinces
- **GET** `/api/indonesia/provinces/{code}` - Province by code

### 🏢 Regencies (514+ total)
- **GET** `/api/indonesia/regencies` - All regencies  
- **GET** `/api/indonesia/provinces/{code}/regencies` - Regencies by province
- **GET** `/api/indonesia/regencies/{code}` - Regency by code

### 🏘️ Districts (7,000+ total)
- **GET** `/api/indonesia/districts` - All districts
- **GET** `/api/indonesia/regencies/{code}/districts` - Districts by regency
- **GET** `/api/indonesia/districts/{code}` - District by code

### 🏠 Villages (83,000+ total)
- **GET** `/api/indonesia/villages` - All villages ⚠️ Large dataset
- **GET** `/api/indonesia/districts/{code}/villages` - Villages by district
- **GET** `/api/indonesia/villages/{code}` - Village by code

### 🔍 Search
- **GET** `/api/indonesia/search?query={term}` - Search all areas
- **GET** `/api/indonesia/search?query={term}&type=provinces` - Search provinces only
- **GET** `/api/indonesia/search?query={term}&type=regencies` - Search regencies only
- **GET** `/api/indonesia/search?query={term}&type=districts` - Search districts only
- **GET** `/api/indonesia/search?query={term}&type=villages` - Search villages only

## 🔧 Authentication Details

### HMAC SHA256 Signature
The API uses HMAC SHA256 authentication with the following flow:

1. **Generate timestamp** - Unix timestamp
2. **Generate nonce** - 32-character random hex string
3. **Create message** - `access_key + timestamp + nonce`
4. **Generate signature** - `HMAC_SHA256(message, secret_key)`
5. **Send authentication request** with all parameters

### Request Headers
```
Accept: application/json
Authorization: Bearer {jwt_token}
Content-Type: application/json
```

## 📋 Code Structure Mapping

This collection matches your Laravel package structure:

```php
// Your Laravel package usage
$manager = new IdnAreaManager(['mode' => 'api']);

// Maps to these API endpoints:
$provinces = $manager->provinces();        // GET /api/indonesia/provinces
$regencies = $manager->getRegencies('11'); // GET /api/indonesia/provinces/11/regencies  
$districts = $manager->getDistricts('1101'); // GET /api/indonesia/regencies/1101/districts
$villages = $manager->getVillages('110101'); // GET /api/indonesia/districts/110101/villages
$search = $manager->search('jakarta');     // GET /api/indonesia/search?query=jakarta
```

## ⚡ Performance Tips

1. **Use hierarchical endpoints** instead of "get all" for better performance:
   - ✅ `/provinces/{code}/regencies` 
   - ❌ `/regencies` (all 514+)

2. **Cache responses** - Data doesn't change frequently
3. **Use search filtering** - Add `type` parameter to limit results
4. **Villages endpoint warning** - 83,000+ records, prefer filtering by district

## 🔄 Token Management

- **Token expiry:** 1 hour
- **Auto-refresh:** Collection handles re-authentication
- **Storage:** Token stored in environment variable `auth_token`
- **Error handling:** 401 responses trigger re-authentication

## 📈 Example Usage Scenarios

### Scenario 1: Dropdown Population
```
1. GET /api/indonesia/provinces → Populate province dropdown
2. User selects province → GET /api/indonesia/provinces/{code}/regencies  
3. User selects regency → GET /api/indonesia/regencies/{code}/districts
4. User selects district → GET /api/indonesia/districts/{code}/villages
```

### Scenario 2: Search Implementation
```
1. User types "jakarta" → GET /api/indonesia/search?query=jakarta
2. Filter by type → GET /api/indonesia/search?query=jakarta&type=regencies
3. Get specific result → GET /api/indonesia/regencies/{code}
```

### Scenario 3: Data Validation
```
1. Validate province → GET /api/indonesia/provinces/{code}
2. Validate regency → GET /api/indonesia/regencies/{code}
3. Check hierarchy → Verify province_code matches
```

## 🛡️ Security Notes

- Store `access_key` and `secret_key` securely
- Use HTTPS only (enforced by API)
- Rotate keys periodically
- Monitor API usage and quotas
- Never expose keys in client-side code

## 🆘 Troubleshooting

### Common Issues

1. **401 Unauthorized**
   - Check access_key and secret_key are correct
   - Verify HMAC signature generation
   - Ensure timestamp is current (within 5 minutes)

2. **403 Forbidden**  
   - API quota exceeded
   - IP restrictions
   - Account limitations

3. **404 Not Found**
   - Invalid area code
   - Endpoint typo
   - Area doesn't exist

4. **429 Too Many Requests**
   - Rate limit exceeded
   - Implement request throttling
   - Consider caching responses

### Debug Steps

1. Test authentication endpoint first
2. Verify environment variables are set
3. Check Postman console for HMAC generation
4. Validate area codes using smaller datasets
5. Monitor response times and implement timeouts

## 📞 Support

- **DataToko API Documentation:** [data.toko.center](https://data.toko.center)
- **Laravel Package Issues:** [GitHub Issues](https://github.com/zaidysf/idn-area-laravel/issues)
- **Package Documentation:** Check your project's README.md

---

## 🏷️ Collection Metadata

- **API Version:** Compatible with your Laravel package v3.0.0
- **Collection Version:** 1.0.0
- **Last Updated:** 2025-01-10
- **Total Endpoints:** 20+ endpoints
- **Authentication:** HMAC SHA256 + JWT Bearer
- **Data Source:** Official BPS (Badan Pusat Statistik) via DataToko