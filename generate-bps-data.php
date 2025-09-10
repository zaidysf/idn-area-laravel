<?php

/**
 * Generate COMPLETE BPS CSV data files for package distribution
 * Fetches ALL data from official BPS government API
 * By zaidysf - Complete dataset for production use
 */
$baseUrl = 'https://sig.bps.go.id/rest-drop-down/';
$outputDir = __DIR__.'/database/seeders/data';
$period = '2024_1.2025';

// Create output directory
if (! is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

echo "🇮🇩 Fetching COMPLETE BPS Data - by zaidysf\n";
echo "Official Government API: {$baseUrl}\n";
echo "Period: {$period}\n";
echo "Target: ALL provinces, regencies, districts, and villages\n";
echo "=========================================\n";

// Function to fetch data with proper error handling
function fetchBpsData($endpoint, $params = [])
{
    global $baseUrl;

    $url = $baseUrl.$endpoint;
    if (! empty($params)) {
        $url .= '?'.http_build_query($params);
    }

    echo "   Calling: {$url}\n";

    // Use curl for better error handling
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 45);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Indonesian Area Data Package by zaidysf');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        echo "   ❌ cURL Error: {$error}\n";

        return false;
    }

    if ($httpCode !== 200) {
        echo "   ❌ HTTP Error: {$httpCode}\n";

        return false;
    }

    if (empty($response)) {
        echo "   ❌ Empty response\n";

        return false;
    }

    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo '   ❌ JSON Error: '.json_last_error_msg()."\n";
        echo '   Response: '.substr($response, 0, 200)."...\n";

        return false;
    }

    return $data;
}

// 1. Fetch ALL Provinces
echo "📍 Fetching ALL provinces from BPS API...\n";
$provinces = fetchBpsData('getwilayah', [
    'level' => 'provinsi',
    'periode_merge' => $period,
]);

if (! $provinces || empty($provinces)) {
    exit("❌ Failed to fetch provinces\n");
}

$provinceCsv = "code,name\n";
foreach ($provinces as $province) {
    $code = $province['kode'] ?? $province['value'] ?? $province['id'];
    $name = $province['nama'] ?? $province['label'] ?? $province['name'];
    $name = str_replace('"', '""', $name);
    $provinceCsv .= "{$code},\"{$name}\"\n";
}

file_put_contents($outputDir.'/provinces.csv', $provinceCsv);
echo '✅ '.count($provinces)." provinces saved to provinces.csv\n\n";

// 2. Fetch ALL Regencies for ALL Provinces
echo "🏢 Fetching ALL regencies from BPS API...\n";
$regencyCsv = "code,province_code,name\n";
$totalRegencies = 0;

// Parse provinces to get codes
$provinceLines = explode("\n", $provinceCsv);
array_shift($provinceLines); // Remove header
$provinceLines = array_filter($provinceLines); // Remove empty lines

foreach ($provinceLines as $provinceLine) {
    $provinceData = str_getcsv($provinceLine, ',', '"', '\\');
    if (count($provinceData) >= 2) {
        $provinceCode = $provinceData[0];
        $provinceName = trim($provinceData[1], '"');

        echo "   Processing Province: {$provinceName} ({$provinceCode})\n";

        $regencies = fetchBpsData('getwilayah', [
            'level' => 'kabkot',
            'parent' => $provinceCode,
            'periode_merge' => $period,
        ]);

        if ($regencies && ! empty($regencies)) {
            foreach ($regencies as $regency) {
                $code = $regency['kode'] ?? $regency['value'] ?? $regency['id'];
                $name = $regency['nama'] ?? $regency['label'] ?? $regency['name'];
                $name = str_replace('"', '""', $name);
                $regencyCsv .= "{$code},{$provinceCode},\"{$name}\"\n";
                $totalRegencies++;
            }
            echo '     → '.count($regencies)." regencies\n";
        } else {
            echo "     → No regencies found\n";
        }

        // Small delay to be respectful to the API
        sleep(1);
    }
}

file_put_contents($outputDir.'/regencies.csv', $regencyCsv);
echo "✅ {$totalRegencies} regencies saved to regencies.csv\n\n";

// 3. Fetch ALL Districts for ALL Regencies
echo "🏘️ Fetching ALL districts from BPS API...\n";
echo "Warning: This will take a while - fetching districts for all {$totalRegencies} regencies\n";
$districtCsv = "code,regency_code,name\n";
$totalDistricts = 0;
$processedRegencies = 0;

// Parse regencies to get list
$regencyLines = explode("\n", $regencyCsv);
array_shift($regencyLines); // Remove header
$regencyLines = array_filter($regencyLines); // Remove empty lines

foreach ($regencyLines as $regencyLine) {
    $regencyData = str_getcsv($regencyLine, ',', '"', '\\');
    if (count($regencyData) >= 3) {
        $regencyCode = $regencyData[0];
        $regencyName = trim($regencyData[2], '"');

        $processedRegencies++;
        echo "   [{$processedRegencies}/{$totalRegencies}] Processing Regency: {$regencyName} ({$regencyCode})\n";

        $districts = fetchBpsData('getwilayah', [
            'level' => 'kecamatan',
            'parent' => $regencyCode,
            'periode_merge' => $period,
        ]);

        if ($districts && ! empty($districts)) {
            foreach ($districts as $district) {
                $code = $district['kode'] ?? $district['value'] ?? $district['id'];
                $name = $district['nama'] ?? $district['label'] ?? $district['name'];
                $name = str_replace('"', '""', $name);
                $districtCsv .= "{$code},{$regencyCode},\"{$name}\"\n";
                $totalDistricts++;
            }
            echo '     → '.count($districts)." districts (total: {$totalDistricts})\n";
        } else {
            echo "     → No districts found\n";
        }

        // Small delay every 10 requests
        if ($processedRegencies % 10 == 0) {
            echo "   [CHECKPOINT] Processed {$processedRegencies}/{$totalRegencies} regencies, total districts: {$totalDistricts}\n";
            sleep(2);
        } else {
            sleep(1);
        }
    }
}

file_put_contents($outputDir.'/districts.csv', $districtCsv);
echo "✅ {$totalDistricts} districts saved to districts.csv\n\n";

// 4. Count villages first to show estimate
echo "🏡 Counting villages (this may take a moment)...\n";
$districtLines = explode("\n", $districtCsv);
array_shift($districtLines); // Remove header
$districtLines = array_filter($districtLines); // Remove empty lines
$totalDistrictCount = count($districtLines);

echo "Found {$totalDistrictCount} districts total\n";
echo 'Estimated villages: ~'.($totalDistrictCount * 12)." (avg 12 per district)\n";
echo 'This will take approximately '.round($totalDistrictCount * 2 / 60)." minutes\n\n";

if (! readline('Continue with village fetching? This will fetch ALL villages (y/n): ') === 'y') {
    echo "Stopping at districts. You can run the sync command later for complete data.\n";
    exit(0);
}

// 5. Fetch ALL Villages for ALL Districts
echo "🏡 Fetching ALL villages from BPS API...\n";
echo "Warning: This will take a LONG time - fetching villages for all {$totalDistrictCount} districts\n";
$villageCsv = "code,district_code,name\n";
$totalVillages = 0;
$processedDistricts = 0;

foreach ($districtLines as $districtLine) {
    $districtData = str_getcsv($districtLine, ',', '"', '\\');
    if (count($districtData) >= 3) {
        $districtCode = $districtData[0];
        $districtName = trim($districtData[2], '"');

        $processedDistricts++;
        echo "   [{$processedDistricts}/{$totalDistrictCount}] Processing District: {$districtName} ({$districtCode})\n";

        $villages = fetchBpsData('getwilayah', [
            'level' => 'desa',
            'parent' => $districtCode,
            'periode_merge' => $period,
        ]);

        if ($villages && ! empty($villages)) {
            foreach ($villages as $village) {
                $code = $village['kode'] ?? $village['value'] ?? $village['id'];
                $name = $village['nama'] ?? $village['label'] ?? $village['name'];
                $name = str_replace('"', '""', $name);
                $villageCsv .= "{$code},{$districtCode},\"{$name}\"\n";
                $totalVillages++;
            }
            echo '     → '.count($villages)." villages (total: {$totalVillages})\n";
        } else {
            echo "     → No villages found\n";
        }

        // Progress checkpoint every 50 districts
        if ($processedDistricts % 50 == 0) {
            echo "   [CHECKPOINT] Processed {$processedDistricts}/{$totalDistrictCount} districts, total villages: {$totalVillages}\n";
            file_put_contents($outputDir.'/villages_partial.csv', $villageCsv); // Save partial progress
            sleep(3);
        } else {
            sleep(1);
        }
    }
}

file_put_contents($outputDir.'/villages.csv', $villageCsv);
echo "✅ {$totalVillages} villages saved to villages.csv\n\n";

// Clean up partial file if it exists
if (file_exists($outputDir.'/villages_partial.csv')) {
    unlink($outputDir.'/villages_partial.csv');
}

// Summary
echo "🎉 COMPLETE BPS Data Generation Finished!\n";
echo "==========================================\n";
echo "Data Source: Official BPS (Badan Pusat Statistik) Indonesia\n";
echo "API Endpoint: {$baseUrl}getwilayah\n";
echo "Period Used: {$period}\n\n";

echo "Generated Files (COMPLETE DATASET):\n";
echo '• provinces.csv - '.count($provinces)." provinces\n";
echo "• regencies.csv - {$totalRegencies} regencies\n";
echo "• districts.csv - {$totalDistricts} districts\n";
echo "• villages.csv - {$totalVillages} villages\n\n";

echo "📁 Files saved to: {$outputDir}/\n\n";

echo "📊 Indonesian Administrative Hierarchy (BPS 2024):\n";
echo "🏛️  {$provinces} Provinces → 🏢 {$totalRegencies} Regencies → 🏘️ {$totalDistricts} Districts → 🏡 {$totalVillages} Villages\n\n";

echo "✅ COMPLETE Indonesian Government Data Successfully Generated!\n";
echo "🇮🇩 Official BPS (Badan Pusat Statistik) Data\n";
echo "💖 Generated by zaidysf Indonesian Area Data Package\n\n";

echo "Next steps:\n";
echo "• These CSV files will be bundled with the package\n";
echo "• Users can seed immediately without API calls\n";
echo "• Full sync command available for updates: php artisan idn-area:sync-bps\n";
