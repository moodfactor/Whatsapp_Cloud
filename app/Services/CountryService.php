<?php

namespace App\Services;

class CountryService
{
    /**
     * Country codes and their information
     */
    private static $countries = [
        // Arab Countries
        '20' => ['code' => 'EG', 'name' => 'Egypt', 'flag' => '🇪🇬', 'arabic' => true],
        '966' => ['code' => 'SA', 'name' => 'Saudi Arabia', 'flag' => '🇸🇦', 'arabic' => true],
        '971' => ['code' => 'AE', 'name' => 'United Arab Emirates', 'flag' => '🇦🇪', 'arabic' => true],
        '965' => ['code' => 'KW', 'name' => 'Kuwait', 'flag' => '🇰🇼', 'arabic' => true],
        '974' => ['code' => 'QA', 'name' => 'Qatar', 'flag' => '🇶🇦', 'arabic' => true],
        '973' => ['code' => 'BH', 'name' => 'Bahrain', 'flag' => '🇧🇭', 'arabic' => true],
        '968' => ['code' => 'OM', 'name' => 'Oman', 'flag' => '🇴🇲', 'arabic' => true],
        '964' => ['code' => 'IQ', 'name' => 'Iraq', 'flag' => '🇮🇶', 'arabic' => true],
        '962' => ['code' => 'JO', 'name' => 'Jordan', 'flag' => '🇯🇴', 'arabic' => true],
        '961' => ['code' => 'LB', 'name' => 'Lebanon', 'flag' => '🇱🇧', 'arabic' => true],
        '963' => ['code' => 'SY', 'name' => 'Syria', 'flag' => '🇸🇾', 'arabic' => true],
        '970' => ['code' => 'PS', 'name' => 'Palestine', 'flag' => '🇵🇸', 'arabic' => true],
        '212' => ['code' => 'MA', 'name' => 'Morocco', 'flag' => '🇲🇦', 'arabic' => true],
        '213' => ['code' => 'DZ', 'name' => 'Algeria', 'flag' => '🇩🇿', 'arabic' => true],
        '216' => ['code' => 'TN', 'name' => 'Tunisia', 'flag' => '🇹🇳', 'arabic' => true],
        '218' => ['code' => 'LY', 'name' => 'Libya', 'flag' => '🇱🇾', 'arabic' => true],
        '249' => ['code' => 'SD', 'name' => 'Sudan', 'flag' => '🇸🇩', 'arabic' => true],
        '967' => ['code' => 'YE', 'name' => 'Yemen', 'flag' => '🇾🇪', 'arabic' => true],
        '252' => ['code' => 'SO', 'name' => 'Somalia', 'flag' => '🇸🇴', 'arabic' => true],
        '253' => ['code' => 'DJ', 'name' => 'Djibouti', 'flag' => '🇩🇯', 'arabic' => true],
        '222' => ['code' => 'MR', 'name' => 'Mauritania', 'flag' => '🇲🇷', 'arabic' => true],

        // Popular Non-Arab Countries
        '1' => ['code' => 'US', 'name' => 'United States', 'flag' => '🇺🇸', 'arabic' => false],
        '44' => ['code' => 'GB', 'name' => 'United Kingdom', 'flag' => '🇬🇧', 'arabic' => false],
        '33' => ['code' => 'FR', 'name' => 'France', 'flag' => '🇫🇷', 'arabic' => false],
        '49' => ['code' => 'DE', 'name' => 'Germany', 'flag' => '🇩🇪', 'arabic' => false],
        '39' => ['code' => 'IT', 'name' => 'Italy', 'flag' => '🇮🇹', 'arabic' => false],
        '34' => ['code' => 'ES', 'name' => 'Spain', 'flag' => '🇪🇸', 'arabic' => false],
        '7' => ['code' => 'RU', 'name' => 'Russia', 'flag' => '🇷🇺', 'arabic' => false],
        '86' => ['code' => 'CN', 'name' => 'China', 'flag' => '🇨🇳', 'arabic' => false],
        '81' => ['code' => 'JP', 'name' => 'Japan', 'flag' => '🇯🇵', 'arabic' => false],
        '82' => ['code' => 'KR', 'name' => 'South Korea', 'flag' => '🇰🇷', 'arabic' => false],
        '91' => ['code' => 'IN', 'name' => 'India', 'flag' => '🇮🇳', 'arabic' => false],
        '55' => ['code' => 'BR', 'name' => 'Brazil', 'flag' => '🇧🇷', 'arabic' => false],
        '90' => ['code' => 'TR', 'name' => 'Turkey', 'flag' => '🇹🇷', 'arabic' => false],
        '98' => ['code' => 'IR', 'name' => 'Iran', 'flag' => '🇮🇷', 'arabic' => false],
        '92' => ['code' => 'PK', 'name' => 'Pakistan', 'flag' => '🇵🇰', 'arabic' => false],
        '60' => ['code' => 'MY', 'name' => 'Malaysia', 'flag' => '🇲🇾', 'arabic' => false],
        '62' => ['code' => 'ID', 'name' => 'Indonesia', 'flag' => '🇮🇩', 'arabic' => false],
        '66' => ['code' => 'TH', 'name' => 'Thailand', 'flag' => '🇹🇭', 'arabic' => false],
        '84' => ['code' => 'VN', 'name' => 'Vietnam', 'flag' => '🇻🇳', 'arabic' => false],
        '65' => ['code' => 'SG', 'name' => 'Singapore', 'flag' => '🇸🇬', 'arabic' => false],
        '61' => ['code' => 'AU', 'name' => 'Australia', 'flag' => '🇦🇺', 'arabic' => false],
        '64' => ['code' => 'NZ', 'name' => 'New Zealand', 'flag' => '🇳🇿', 'arabic' => false],
    ];

    /**
     * Normalize phone number to international format
     */
    public static function normalizePhoneNumber(string $phone): string
    {
        // Remove all non-digit characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Add '+' if not present
        if (!empty($phone) && $phone[0] !== '+') {
            $phone = '+' . $phone;
        }
        
        return $phone;
    }

    /**
     * Get country information from phone number
     */
    public static function getCountryFromPhone(string $phone): array
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Try different lengths of country codes (1-4 digits)
        for ($length = 4; $length >= 1; $length--) {
            $countryCode = substr($phone, 0, $length);
            if (isset(self::$countries[$countryCode])) {
                return array_merge(self::$countries[$countryCode], [
                    'country_code' => $countryCode,
                    'phone_number' => $phone
                ]);
            }
        }
        
        // Default unknown country
        return [
            'code' => 'XX',
            'name' => 'Unknown',
            'flag' => '🌍',
            'arabic' => false,
            'country_code' => '',
            'phone_number' => $phone
        ];
    }

    /**
     * Get display name with country flag
     */
    public static function getDisplayName(string $phone, string $contactName = 'Unknown'): string
    {
        $countryInfo = self::getCountryFromPhone($phone);
        $flag = $countryInfo['flag'];
        
        // Create display name with flag
        return $flag . ' ' . ($contactName ?: 'Customer');
    }

    /**
     * Get masked phone number for privacy
     */
    public static function getMaskedPhone(string $phone, bool $isSuperAdmin = false): string
    {
        if ($isSuperAdmin) {
            return self::normalizePhoneNumber($phone);
        }
        
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        if (strlen($phone) <= 4) {
            return '****';
        }
        
        // Show first 3 and last 3 digits, mask the middle
        $start = substr($phone, 0, 3);
        $end = substr($phone, -3);
        $middle = str_repeat('*', max(0, strlen($phone) - 6));
        
        return "+{$start}{$middle}{$end}";
    }

    /**
     * Check if phone is from Arab country
     */
    public static function isArabCountry(string $phone): bool
    {
        $countryInfo = self::getCountryFromPhone($phone);
        return $countryInfo['arabic'] ?? false;
    }

    /**
     * Get contact ID for display (masked for privacy)
     */
    public static function getContactId(string $phone, string $contactName = '', bool $isSuperAdmin = false): string
    {
        $countryInfo = self::getCountryFromPhone($phone);
        $flag = $countryInfo['flag'];
        
        if (!empty($contactName) && $contactName !== 'Unknown') {
            return $flag . ' ' . $contactName;
        }
        
        if ($isSuperAdmin) {
            return $flag . ' ' . self::normalizePhoneNumber($phone);
        }
        
        // Generate a unique but privacy-safe ID
        $hashedId = substr(md5($phone), 0, 6);
        return $flag . ' Contact #' . strtoupper($hashedId);
    }

    /**
     * Get all Arab countries
     */
    public static function getArabCountries(): array
    {
        return array_filter(self::$countries, function($country) {
            return $country['arabic'];
        });
    }

    /**
     * Get country flag by country code
     */
    public static function getFlagByCountryCode(string $countryCode): string
    {
        foreach (self::$countries as $code => $info) {
            if ($info['code'] === strtoupper($countryCode)) {
                return $info['flag'];
            }
        }
        return '🌍';
    }

    /**
     * Format phone for display with country flag and privacy
     */
    public static function formatPhoneForDisplay(string $phone, string $contactName = '', bool $canSeePhone = false): array
    {
        $countryInfo = self::getCountryFromPhone($phone);
        
        return [
            'display_name' => self::getContactId($phone, $contactName, $canSeePhone),
            'display_phone' => self::getMaskedPhone($phone, $canSeePhone),
            'country_flag' => $countryInfo['flag'],
            'country_name' => $countryInfo['name'],
            'country_code' => $countryInfo['country_code'],
            'is_arab' => $countryInfo['arabic'],
            'full_phone' => $canSeePhone ? self::normalizePhoneNumber($phone) : null
        ];
    }
}