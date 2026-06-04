<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PortGeocoderService
{
    /** @var array<string, array{lat: float, lng: float, name?: string}> */
    protected const KNOWN_PORTS = [
        'NEW YORK' => ['lat' => 40.6840, 'lng' => -74.0060, 'name' => 'New York, US'],
        'NY' => ['lat' => 40.6840, 'lng' => -74.0060, 'name' => 'New York, US'],
        'NYC' => ['lat' => 40.6840, 'lng' => -74.0060, 'name' => 'New York, US'],
        'LOS ANGELES' => ['lat' => 33.7405, 'lng' => -118.2720, 'name' => 'Los Angeles, US'],
        'LA' => ['lat' => 33.7405, 'lng' => -118.2720, 'name' => 'Los Angeles, US'],
        'LONG BEACH' => ['lat' => 33.7540, 'lng' => -118.2160, 'name' => 'Long Beach, US'],
        'HOUSTON' => ['lat' => 29.7355, 'lng' => -95.2650, 'name' => 'Houston, US'],
        'SAVANNAH' => ['lat' => 32.0809, 'lng' => -81.0912, 'name' => 'Savannah, US'],
        'MIAMI' => ['lat' => 25.7781, 'lng' => -80.1790, 'name' => 'Miami, US'],
        'JACKSONVILLE' => ['lat' => 30.3984, 'lng' => -81.5580, 'name' => 'Jacksonville, US'],
        'BALTIMORE' => ['lat' => 39.2660, 'lng' => -76.5780, 'name' => 'Baltimore, US'],
        'NORFOLK' => ['lat' => 36.9460, 'lng' => -76.3300, 'name' => 'Norfolk, US'],
        'NEWARK' => ['lat' => 40.6865, 'lng' => -74.1570, 'name' => 'Newark, US'],
        'NEWARK NJ' => ['lat' => 40.6865, 'lng' => -74.1570, 'name' => 'Newark, US'],
        'PHILADELPHIA' => ['lat' => 39.8740, 'lng' => -75.1370, 'name' => 'Philadelphia, US'],
        'BOSTON' => ['lat' => 42.3601, 'lng' => -71.0589, 'name' => 'Boston, US'],
        'SEATTLE' => ['lat' => 47.5980, 'lng' => -122.3480, 'name' => 'Seattle, US'],
        'TACOMA' => ['lat' => 47.2670, 'lng' => -122.4140, 'name' => 'Tacoma, US'],
        'OAKLAND' => ['lat' => 37.8044, 'lng' => -122.3000, 'name' => 'Oakland, US'],
        'CHARLESTON' => ['lat' => 32.7870, 'lng' => -79.9250, 'name' => 'Charleston, US'],
        'MOBILE' => ['lat' => 30.6954, 'lng' => -88.0399, 'name' => 'Mobile, US'],
        'NEW ORLEANS' => ['lat' => 29.9360, 'lng' => -90.0610, 'name' => 'New Orleans, US'],
        'PORT EVERGLADES' => ['lat' => 26.0880, 'lng' => -80.1160, 'name' => 'Port Everglades, US'],
        'FREEPORT' => ['lat' => 28.9540, 'lng' => -95.3560, 'name' => 'Freeport, US'],
        'TORONTO' => ['lat' => 43.6532, 'lng' => -79.3832, 'name' => 'Toronto, CA'],
        'MONTREAL' => ['lat' => 45.5017, 'lng' => -73.5673, 'name' => 'Montreal, CA'],
        'VANCOUVER' => ['lat' => 49.2827, 'lng' => -123.1207, 'name' => 'Vancouver, CA'],
        'HALIFAX' => ['lat' => 44.6488, 'lng' => -63.5752, 'name' => 'Halifax, CA'],
        'PRINCE RUPERT' => ['lat' => 54.3150, 'lng' => -130.3200, 'name' => 'Prince Rupert, CA'],
        'BRAMPTON' => ['lat' => 43.6532, 'lng' => -79.3832, 'name' => 'Toronto area, CA'],
        'CALGARY' => ['lat' => 51.0447, 'lng' => -114.0719, 'name' => 'Calgary, CA'],
        'MERSIN' => ['lat' => 36.8000, 'lng' => 34.6330, 'name' => 'Mersin, TR'],
        'MERSIN PORT' => ['lat' => 36.8000, 'lng' => 34.6330, 'name' => 'Mersin, TR'],
        'ISTANBUL' => ['lat' => 40.9860, 'lng' => 28.9200, 'name' => 'Istanbul, TR'],
        'AMBARLI' => ['lat' => 40.9660, 'lng' => 28.6900, 'name' => 'Ambarli, TR'],
        'IZMIT' => ['lat' => 40.7650, 'lng' => 29.9400, 'name' => 'Izmit, TR'],
        'GEMLIK' => ['lat' => 40.4300, 'lng' => 29.1550, 'name' => 'Gemlik, TR'],
        'GEBZE' => ['lat' => 40.8028, 'lng' => 29.4307, 'name' => 'Gebze, TR'],
        'IZMIR' => ['lat' => 38.4237, 'lng' => 27.1428, 'name' => 'Izmir, TR'],
        'ALIAGA' => ['lat' => 38.8000, 'lng' => 26.9700, 'name' => 'Aliaga, TR'],
        'PIRAEUS' => ['lat' => 37.9420, 'lng' => 23.6460, 'name' => 'Piraeus, GR'],
        'THESSALONIKI' => ['lat' => 40.6401, 'lng' => 22.9444, 'name' => 'Thessaloniki, GR'],
        'BARCELONA' => ['lat' => 41.3580, 'lng' => 2.1740, 'name' => 'Barcelona, ES'],
        'VALENCIA' => ['lat' => 39.4520, 'lng' => -0.3250, 'name' => 'Valencia, ES'],
        'GENOA' => ['lat' => 44.4056, 'lng' => 8.9463, 'name' => 'Genoa, IT'],
        'LIVORNO' => ['lat' => 43.5485, 'lng' => 10.3100, 'name' => 'Livorno, IT'],
        'LA SPEZIA' => ['lat' => 44.1025, 'lng' => 9.8240, 'name' => 'La Spezia, IT'],
        'MARSEILLE' => ['lat' => 43.2965, 'lng' => 5.3698, 'name' => 'Marseille, FR'],
        'LE HAVRE' => ['lat' => 49.4944, 'lng' => 0.1079, 'name' => 'Le Havre, FR'],
        'ROTTERDAM' => ['lat' => 51.9496, 'lng' => 4.1453, 'name' => 'Rotterdam, NL'],
        'HAMBURG' => ['lat' => 53.5461, 'lng' => 9.9663, 'name' => 'Hamburg, DE'],
        'BREMERHAVEN' => ['lat' => 53.5396, 'lng' => 8.5809, 'name' => 'Bremerhaven, DE'],
        'ANTWERP' => ['lat' => 51.2794, 'lng' => 4.4163, 'name' => 'Antwerp, BE'],
        'FELIXSTOWE' => ['lat' => 51.9550, 'lng' => 1.3510, 'name' => 'Felixstowe, GB'],
        'SOUTHAMPTON' => ['lat' => 50.9097, 'lng' => -1.4044, 'name' => 'Southampton, GB'],
        'LONDON GATEWAY' => ['lat' => 51.5070, 'lng' => 0.5340, 'name' => 'London Gateway, GB'],
        'GDANSK' => ['lat' => 54.3520, 'lng' => 18.6466, 'name' => 'Gdansk, PL'],
        'GDYNIA' => ['lat' => 54.5189, 'lng' => 18.5305, 'name' => 'Gdynia, PL'],
        'CONSTANTA' => ['lat' => 44.1598, 'lng' => 28.6348, 'name' => 'Constanta, RO'],
        'POTI' => ['lat' => 42.1500, 'lng' => 41.6700, 'name' => 'Poti, GE'],
        'HAIFA' => ['lat' => 32.8190, 'lng' => 35.0000, 'name' => 'Haifa, IL'],
        'ASHDOD' => ['lat' => 31.8040, 'lng' => 34.6550, 'name' => 'Ashdod, IL'],
        'BEIRUT' => ['lat' => 33.8938, 'lng' => 35.5018, 'name' => 'Beirut, LB'],
        'ALEXANDRIA' => ['lat' => 31.2001, 'lng' => 29.9187, 'name' => 'Alexandria, EG'],
        'PORT SAID' => ['lat' => 31.2653, 'lng' => 32.3019, 'name' => 'Port Said, EG'],
        'DAMIETTA' => ['lat' => 31.4175, 'lng' => 31.8144, 'name' => 'Damietta, EG'],
        'MISURATA' => ['lat' => 32.3754, 'lng' => 15.0920, 'name' => 'Misurata, LY'],
        'TRIPOLI' => ['lat' => 32.8872, 'lng' => 13.1913, 'name' => 'Tripoli, LY'],
        'JEDDAH' => ['lat' => 21.4858, 'lng' => 39.1925, 'name' => 'Jeddah, SA'],
        'JEDDA' => ['lat' => 21.4858, 'lng' => 39.1925, 'name' => 'Jeddah, SA'],
        'DAMMAM' => ['lat' => 26.4367, 'lng' => 50.1039, 'name' => 'Dammam, SA'],
        'JUBAIL' => ['lat' => 27.0174, 'lng' => 49.6225, 'name' => 'Jubail, SA'],
        'YANBU' => ['lat' => 24.0895, 'lng' => 38.0618, 'name' => 'Yanbu, SA'],
        'DUBAI' => ['lat' => 25.2697, 'lng' => 55.3095, 'name' => 'Dubai, AE'],
        'JEBEL ALI' => ['lat' => 25.0260, 'lng' => 55.0610, 'name' => 'Jebel Ali, AE'],
        'ABU DHABI' => ['lat' => 24.4539, 'lng' => 54.3773, 'name' => 'Abu Dhabi, AE'],
        'SHARJAH' => ['lat' => 25.3463, 'lng' => 55.4209, 'name' => 'Sharjah, AE'],
        'KUWAIT' => ['lat' => 29.3759, 'lng' => 47.9774, 'name' => 'Kuwait, KW'],
        'SHUWAIKH' => ['lat' => 29.3470, 'lng' => 47.9360, 'name' => 'Shuwaikh, KW'],
        'BAHRAIN' => ['lat' => 26.2361, 'lng' => 50.6530, 'name' => 'Bahrain, BH'],
        'SALALAH' => ['lat' => 16.9398, 'lng' => 54.0050, 'name' => 'Salalah, OM'],
        'SOHAR' => ['lat' => 24.3640, 'lng' => 56.7430, 'name' => 'Sohar, OM'],
        'AQABA' => ['lat' => 29.5320, 'lng' => 35.0060, 'name' => 'Aqaba, JO'],
        'UMM QASR' => ['lat' => 30.0340, 'lng' => 47.9290, 'name' => 'Umm Qasr, IQ'],
        'BASRA' => ['lat' => 30.0340, 'lng' => 47.9290, 'name' => 'Basra, IQ'],
        'SINGAPORE' => ['lat' => 1.2640, 'lng' => 103.8200, 'name' => 'Singapore, SG'],
        'PORT KLANG' => ['lat' => 3.0030, 'lng' => 101.3990, 'name' => 'Port Klang, MY'],
        'SHANGHAI' => ['lat' => 31.2304, 'lng' => 121.4737, 'name' => 'Shanghai, CN'],
        'NINGBO' => ['lat' => 29.8683, 'lng' => 121.5440, 'name' => 'Ningbo, CN'],
        'QINGDAO' => ['lat' => 36.0671, 'lng' => 120.3826, 'name' => 'Qingdao, CN'],
        'TIANJIN' => ['lat' => 39.0842, 'lng' => 117.2010, 'name' => 'Tianjin, CN'],
        'DALIAN' => ['lat' => 38.9140, 'lng' => 121.6147, 'name' => 'Dalian, CN'],
        'BUSAN' => ['lat' => 35.1796, 'lng' => 129.0756, 'name' => 'Busan, KR'],
        'YOKOHAMA' => ['lat' => 35.4437, 'lng' => 139.6380, 'name' => 'Yokohama, JP'],
        'TOKYO' => ['lat' => 35.6528, 'lng' => 139.8390, 'name' => 'Tokyo, JP'],
        'NAGOYA' => ['lat' => 35.0844, 'lng' => 136.8815, 'name' => 'Nagoya, JP'],
        'KOBE' => ['lat' => 34.6785, 'lng' => 135.1955, 'name' => 'Kobe, JP'],
        'LAEM CHABANG' => ['lat' => 13.0827, 'lng' => 100.8830, 'name' => 'Laem Chabang, TH'],
        'NHAVA SHEVA' => ['lat' => 18.9490, 'lng' => 72.9510, 'name' => 'Nhava Sheva, IN'],
        'JNPT' => ['lat' => 18.9490, 'lng' => 72.9510, 'name' => 'JNPT, IN'],
        'MUNDRA' => ['lat' => 22.8390, 'lng' => 69.7210, 'name' => 'Mundra, IN'],
        'CHENNAI' => ['lat' => 13.1040, 'lng' => 80.3000, 'name' => 'Chennai, IN'],
        'COLOMBO' => ['lat' => 6.9271, 'lng' => 79.8612, 'name' => 'Colombo, LK'],
        'CHITTAGONG' => ['lat' => 22.3380, 'lng' => 91.8320, 'name' => 'Chittagong, BD'],
        'MANILA' => ['lat' => 14.5995, 'lng' => 120.9842, 'name' => 'Manila, PH'],
    ];

    /**
     * Explicit Nominatim queries for names that match the wrong place with a generic "port" search.
     *
     * @var array<string, string>
     */
    protected const NOMINATIM_QUERIES = [
        'TORONTO' => 'Port of Toronto, Ontario, Canada',
        'MONTREAL' => 'Port of Montreal, Quebec, Canada',
        'VANCOUVER' => 'Port of Vancouver, British Columbia, Canada',
        'MERSIN' => 'Mersin International Port, Turkey',
        'BRAMPTON' => 'Port of Toronto, Ontario, Canada',
    ];

    /** @var array<string, string> ISO 3166-1 alpha-2 */
    protected const COUNTRY_ALIASES = [
        'CANADA' => 'CA',
        'CA' => 'CA',
        'CAN' => 'CA',
        'UNITED STATES' => 'US',
        'UNITED STATES OF AMERICA' => 'US',
        'USA' => 'US',
        'US' => 'US',
        'U.S.' => 'US',
        'U.S.A.' => 'US',
        'AMERICA' => 'US',
        'TURKEY' => 'TR',
        'TURKIYE' => 'TR',
        'TÜRKIYE' => 'TR',
        'TR' => 'TR',
        'SAUDI ARABIA' => 'SA',
        'KSA' => 'SA',
        'SA' => 'SA',
        'UAE' => 'AE',
        'UNITED ARAB EMIRATES' => 'AE',
        'EMIRATES' => 'AE',
        'AE' => 'AE',
        'KUWAIT' => 'KW',
        'KW' => 'KW',
        'JORDAN' => 'JO',
        'JO' => 'JO',
        'IRAQ' => 'IQ',
        'IQ' => 'IQ',
        'OMAN' => 'OM',
        'OM' => 'OM',
        'BAHRAIN' => 'BH',
        'BH' => 'BH',
        'QATAR' => 'QA',
        'QA' => 'QA',
        'EGYPT' => 'EG',
        'EG' => 'EG',
        'LIBYA' => 'LY',
        'LY' => 'LY',
        'LEBANON' => 'LB',
        'LB' => 'LB',
        'ISRAEL' => 'IL',
        'IL' => 'IL',
        'GREECE' => 'GR',
        'GR' => 'GR',
        'ITALY' => 'IT',
        'IT' => 'IT',
        'SPAIN' => 'ES',
        'ES' => 'ES',
        'FRANCE' => 'FR',
        'FR' => 'FR',
        'GERMANY' => 'DE',
        'DE' => 'DE',
        'NETHERLANDS' => 'NL',
        'NL' => 'NL',
        'BELGIUM' => 'BE',
        'BE' => 'BE',
        'UK' => 'GB',
        'UNITED KINGDOM' => 'GB',
        'GREAT BRITAIN' => 'GB',
        'ENGLAND' => 'GB',
        'GB' => 'GB',
        'POLAND' => 'PL',
        'PL' => 'PL',
        'ROMANIA' => 'RO',
        'RO' => 'RO',
        'GEORGIA' => 'GE',
        'GE' => 'GE',
        'CHINA' => 'CN',
        'CN' => 'CN',
        'JAPAN' => 'JP',
        'JP' => 'JP',
        'KOREA' => 'KR',
        'SOUTH KOREA' => 'KR',
        'KR' => 'KR',
        'SINGAPORE' => 'SG',
        'SG' => 'SG',
        'MALAYSIA' => 'MY',
        'MY' => 'MY',
        'THAILAND' => 'TH',
        'TH' => 'TH',
        'INDIA' => 'IN',
        'IN' => 'IN',
        'BANGLADESH' => 'BD',
        'BD' => 'BD',
        'SRI LANKA' => 'LK',
        'LK' => 'LK',
        'PHILIPPINES' => 'PH',
        'PH' => 'PH',
    ];

    /** City token → country hint when no explicit country in the string. */
    protected const CITY_COUNTRY_HINTS = [
        'TORONTO' => 'CA',
        'MONTREAL' => 'CA',
        'VANCOUVER' => 'CA',
        'HALIFAX' => 'CA',
        'BRAMPTON' => 'CA',
        'CALGARY' => 'CA',
        'PRINCE RUPERT' => 'CA',
        'MERSIN' => 'TR',
        'ISTANBUL' => 'TR',
        'IZMIT' => 'TR',
        'GEMLIK' => 'TR',
        'IZMIR' => 'TR',
        'AMBARLI' => 'TR',
        'ALIAGA' => 'TR',
        'NEW YORK' => 'US',
        'NYC' => 'US',
        'LOS ANGELES' => 'US',
        'HOUSTON' => 'US',
        'SAVANNAH' => 'US',
        'MIAMI' => 'US',
        'BALTIMORE' => 'US',
        'NORFOLK' => 'US',
        'NEWARK' => 'US',
        'SEATTLE' => 'US',
        'CHARLESTON' => 'US',
    ];

    /**
     * Country bounding boxes for validation (ports / coastal shipping).
     *
     * @var array<string, array{min_lat: float, max_lat: float, min_lng: float, max_lng: float}>
     */
    protected const COUNTRY_BBOX = [
        'CA' => ['min_lat' => 41.5, 'max_lat' => 70.0, 'min_lng' => -141.0, 'max_lng' => -52.0],
        'US' => ['min_lat' => 24.0, 'max_lat' => 49.5, 'min_lng' => -125.0, 'max_lng' => -66.0],
        'TR' => ['min_lat' => 35.5, 'max_lat' => 42.5, 'min_lng' => 25.5, 'max_lng' => 45.0],
        'SA' => ['min_lat' => 16.0, 'max_lat' => 29.0, 'min_lng' => 34.0, 'max_lng' => 56.0],
        'AE' => ['min_lat' => 22.5, 'max_lat' => 26.5, 'min_lng' => 51.0, 'max_lng' => 56.5],
        'GR' => ['min_lat' => 34.5, 'max_lat' => 41.5, 'min_lng' => 19.0, 'max_lng' => 29.0],
    ];

    protected const PROVIDER_DISAGREE_KM = 800.0;

    /** Reject obvious wrong-continent matches (e.g. Toronto → South Africa). */
    protected const MAX_DISTANCE_FROM_COUNTRY_BBOX_KM = 500.0;

    /**
     * @return array{name: string, lat: float, lng: float, geocoded: bool, geocode_confidence?: string, geocode_provider?: string}|null
     */
    public function resolve(string $label): ?array
    {
        $label = trim($label);

        if ($label === '') {
            return null;
        }

        $cacheKey = 'port_geocode:v3:'.md5(mb_strtolower($label));

        return Cache::remember($cacheKey, now()->addDays(30), function () use ($label) {
            return $this->resolveUncached($label);
        });
    }

    /**
     * @return array{name: string, lat: float, lng: float, geocoded: bool, geocode_confidence: string, geocode_provider?: string}|null
     */
    protected function resolveUncached(string $label): ?array
    {
        $known = $this->matchKnownPort($label);

        if ($known !== null) {
            return [
                'name' => $known['name'] ?? $label,
                'lat' => $known['lat'],
                'lng' => $known['lng'],
                'geocoded' => false,
                'geocode_confidence' => 'high',
                'geocode_provider' => 'known_ports',
            ];
        }

        $countryHint = $this->extractCountryHint($label);
        $normalized = $this->normalizePortLabel($label);

        $candidates = [];

        $nominatim = $this->nominatim($label, $countryHint);

        if ($nominatim !== null) {
            $candidates['nominatim'] = $nominatim;
        }

        $openMeteo = $this->openMeteo($label, $countryHint);

        if ($openMeteo !== null) {
            $candidates['open_meteo'] = $openMeteo;
        }

        $photon = $this->photon($label, $countryHint);

        if ($photon !== null) {
            $candidates['photon'] = $photon;
        }

        $validated = $this->filterValidatedCandidates($candidates, $countryHint);

        if ($validated === []) {
            $this->logGeocodeFailure($label, $countryHint, $candidates);

            return null;
        }

        $picked = $this->reconcileCandidates($validated, $countryHint, $label, $normalized);

        if ($picked === null) {
            $this->logGeocodeFailure($label, $countryHint, $candidates);

            return null;
        }

        return $picked;
    }

    /**
     * @return array{lat: float, lng: float, name?: string}|null
     */
    protected function matchKnownPort(string $label): ?array
    {
        $normalized = $this->normalizePortLabel($label);

        if (isset(self::KNOWN_PORTS[$normalized])) {
            return self::KNOWN_PORTS[$normalized];
        }

        foreach (self::KNOWN_PORTS as $key => $coords) {
            if (str_contains($normalized, $key)) {
                return $coords;
            }
        }

        return null;
    }

    protected function normalizePortLabel(string $label): string
    {
        $normalized = mb_strtoupper(preg_replace('/\s+/', ' ', trim($label)) ?? $label);

        if (str_contains($normalized, ',')) {
            $normalized = trim(explode(',', $normalized, 2)[0]);
        }

        return preg_replace('/\s+PORT$/', '', $normalized) ?? $normalized;
    }

    protected function extractCountryHint(string $label): ?string
    {
        $upper = mb_strtoupper(trim($label));

        if (str_contains($upper, ',')) {
            $parts = array_map('trim', explode(',', $upper));
            $tail = end($parts);

            if (is_string($tail) && $tail !== '') {
                $code = self::COUNTRY_ALIASES[$tail] ?? null;

                if ($code !== null) {
                    return $code;
                }

                foreach (self::COUNTRY_ALIASES as $alias => $iso) {
                    if (str_contains($tail, $alias)) {
                        return $iso;
                    }
                }
            }
        }

        foreach (self::COUNTRY_ALIASES as $alias => $iso) {
            if (str_contains($upper, $alias)) {
                return $iso;
            }
        }

        $cityKey = $this->normalizePortLabel($label);

        return self::CITY_COUNTRY_HINTS[$cityKey] ?? null;
    }

    /**
     * @param  array<string, array{name: string, lat: float, lng: float, geocoded: bool, country_code?: string}>  $candidates
     * @return array<string, array{name: string, lat: float, lng: float, geocoded: bool, country_code?: string}>
     */
    protected function filterValidatedCandidates(array $candidates, ?string $countryHint): array
    {
        $validated = [];

        foreach ($candidates as $provider => $point) {
            if ($this->isValidForHint($point['lat'], $point['lng'], $countryHint, $point['country_code'] ?? null)) {
                $validated[$provider] = $point;
            }
        }

        return $validated;
    }

    protected function isValidForHint(float $lat, float $lng, ?string $countryHint, ?string $resultCountry): bool
    {
        if ($countryHint === null) {
            return true;
        }

        if ($resultCountry !== null && strtoupper($resultCountry) !== $countryHint) {
            return false;
        }

        if (isset(self::COUNTRY_BBOX[$countryHint])) {
            $bbox = self::COUNTRY_BBOX[$countryHint];

            if ($this->pointInBbox($lat, $lng, $bbox)) {
                return true;
            }

            if (in_array($countryHint, ['CA', 'US', 'TR'], true)
                && $this->distanceToBboxKm($lat, $lng, $bbox) > self::MAX_DISTANCE_FROM_COUNTRY_BBOX_KM) {
                return false;
            }

            if (in_array($countryHint, ['CA', 'US', 'TR'], true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array{min_lat: float, max_lat: float, min_lng: float, max_lng: float}  $bbox
     */
    protected function pointInBbox(float $lat, float $lng, array $bbox): bool
    {
        return $lat >= $bbox['min_lat']
            && $lat <= $bbox['max_lat']
            && $lng >= $bbox['min_lng']
            && $lng <= $bbox['max_lng'];
    }

    /**
     * @param  array{min_lat: float, max_lat: float, min_lng: float, max_lng: float}  $bbox
     */
    protected function distanceToBboxKm(float $lat, float $lng, array $bbox): float
    {
        $clampLat = min(max($lat, $bbox['min_lat']), $bbox['max_lat']);
        $clampLng = min(max($lng, $bbox['min_lng']), $bbox['max_lng']);

        return $this->haversineKm($lat, $lng, $clampLat, $clampLng);
    }

    /**
     * @param  array<string, array{name: string, lat: float, lng: float, geocoded: bool, country_code?: string}>  $validated
     * @return array{name: string, lat: float, lng: float, geocoded: bool, geocode_confidence: string, geocode_provider: string}|null
     */
    protected function reconcileCandidates(array $validated, ?string $countryHint, string $label, string $normalized): ?array
    {
        $providers = array_keys($validated);

        if (count($validated) === 1) {
            $only = reset($validated);
            $provider = $providers[0];

            return [
                'name' => $only['name'],
                'lat' => $only['lat'],
                'lng' => $only['lng'],
                'geocoded' => true,
                'geocode_confidence' => $countryHint !== null ? 'medium' : 'low',
                'geocode_provider' => $provider,
            ];
        }

        $clusters = $this->clusterByProximity($validated);

        usort($clusters, fn (array $a, array $b) => count($b) <=> count($a));

        $bestCluster = $clusters[0];
        $bestProviders = array_keys($bestCluster);

        if (count($clusters) > 1) {
            $second = $clusters[1];
            $repA = reset($bestCluster);
            $repB = reset($second);

            if ($this->haversineKm($repA['lat'], $repA['lng'], $repB['lat'], $repB['lng']) > self::PROVIDER_DISAGREE_KM) {
                $inBbox = array_filter($bestCluster, function (array $p) use ($countryHint) {
                    return $countryHint === null
                        || ! isset(self::COUNTRY_BBOX[$countryHint])
                        || $this->pointInBbox($p['lat'], $p['lng'], self::COUNTRY_BBOX[$countryHint]);
                });

                if ($inBbox !== []) {
                    $bestCluster = $inBbox;
                    $bestProviders = array_keys($bestCluster);
                } elseif (isset(self::KNOWN_PORTS[$normalized])) {
                    $known = self::KNOWN_PORTS[$normalized];

                    return [
                        'name' => $known['name'] ?? $label,
                        'lat' => $known['lat'],
                        'lng' => $known['lng'],
                        'geocoded' => false,
                        'geocode_confidence' => 'medium',
                        'geocode_provider' => 'known_ports_fallback',
                    ];
                }
            }
        }

        $latSum = 0.0;
        $lngSum = 0.0;

        foreach ($bestCluster as $point) {
            $latSum += $point['lat'];
            $lngSum += $point['lng'];
        }

        $n = count($bestCluster);
        $first = reset($bestCluster);
        $confidence = count($bestCluster) >= 2 ? 'high' : ($countryHint !== null ? 'medium' : 'low');

        return [
            'name' => $first['name'],
            'lat' => round($latSum / $n, 6),
            'lng' => round($lngSum / $n, 6),
            'geocoded' => true,
            'geocode_confidence' => $confidence,
            'geocode_provider' => implode('+', $bestProviders),
        ];
    }

    /**
     * @param  array<string, array{name: string, lat: float, lng: float, geocoded: bool}>  $validated
     * @return list<array<string, array{name: string, lat: float, lng: float, geocoded: bool}>>
     */
    protected function clusterByProximity(array $validated): array
    {
        $clusters = [];

        foreach ($validated as $provider => $point) {
            $placed = false;

            foreach ($clusters as &$cluster) {
                $rep = reset($cluster);

                if ($this->haversineKm($point['lat'], $point['lng'], $rep['lat'], $rep['lng']) <= self::PROVIDER_DISAGREE_KM) {
                    $cluster[$provider] = $point;
                    $placed = true;

                    break;
                }
            }

            unset($cluster);

            if (! $placed) {
                $clusters[] = [$provider => $point];
            }
        }

        return $clusters;
    }

    /**
     * @return array{name: string, lat: float, lng: float, geocoded: bool, country_code?: string}|null
     */
    protected function nominatim(string $label, ?string $countryHint): ?array
    {
        $normalized = $this->normalizePortLabel($label);
        $query = self::NOMINATIM_QUERIES[$normalized] ?? $this->buildPortSearchQuery($label, $countryHint);

        $params = [
            'q' => $query,
            'format' => 'json',
            'limit' => 3,
        ];

        if ($countryHint !== null) {
            $params['countrycodes'] = strtolower($countryHint);
        }

        $this->throttleExternal('nominatim_last_request', 1.1);

        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::withHeaders([
                'User-Agent' => 'VinstackLite/1.0 (container-tracking; contact@local)',
            ])
                ->timeout(12)
                ->get('https://nominatim.openstreetmap.org/search', $params);
        } catch (RuntimeException) {
            return null;
        }

        if ($response->failed()) {
            return null;
        }

        $results = $response->json();

        if (! is_array($results) || $results === []) {
            return null;
        }

        foreach ($results as $row) {
            if (! is_array($row)) {
                continue;
            }

            $lat = isset($row['lat']) ? (float) $row['lat'] : null;
            $lng = isset($row['lon']) ? (float) $row['lon'] : null;

            if ($lat === null || $lng === null) {
                continue;
            }

            $countryCode = null;

            if (isset($row['address']) && is_array($row['address'])) {
                $countryCode = $row['address']['country_code'] ?? null;
            }

            return [
                'name' => is_string($row['display_name'] ?? null) ? $row['display_name'] : $label,
                'lat' => $lat,
                'lng' => $lng,
                'geocoded' => true,
                'country_code' => is_string($countryCode) ? strtoupper($countryCode) : null,
            ];
        }

        return null;
    }

    /**
     * @return array{name: string, lat: float, lng: float, geocoded: bool, country_code?: string}|null
     */
    protected function openMeteo(string $label, ?string $countryHint): ?array
    {
        $searchName = $this->cityTokenFromLabel($label);

        $params = [
            'name' => $searchName,
            'count' => 5,
            'language' => 'en',
            'format' => 'json',
        ];

        if ($countryHint !== null) {
            $params['country'] = $countryHint;
        }

        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::timeout(10)
                ->get('https://geocoding-api.open-meteo.com/v1/search', $params);
        } catch (RuntimeException) {
            return null;
        }

        if ($response->failed()) {
            return null;
        }

        $payload = $response->json();

        if (! is_array($payload) || empty($payload['results']) || ! is_array($payload['results'])) {
            return null;
        }

        foreach ($payload['results'] as $row) {
            if (! is_array($row)) {
                continue;
            }

            $lat = isset($row['latitude']) ? (float) $row['latitude'] : null;
            $lng = isset($row['longitude']) ? (float) $row['longitude'] : null;

            if ($lat === null || $lng === null) {
                continue;
            }

            $countryCode = isset($row['country_code']) ? strtoupper((string) $row['country_code']) : null;

            if ($countryHint !== null && $countryCode !== null && $countryCode !== $countryHint) {
                continue;
            }

            $name = trim(implode(', ', array_filter([
                $row['name'] ?? null,
                $row['admin1'] ?? null,
                $row['country'] ?? null,
            ])));

            return [
                'name' => $name !== '' ? $name : $label,
                'lat' => $lat,
                'lng' => $lng,
                'geocoded' => true,
                'country_code' => $countryCode,
            ];
        }

        return null;
    }

    /**
     * @return array{name: string, lat: float, lng: float, geocoded: bool, country_code?: string}|null
     */
    protected function photon(string $label, ?string $countryHint): ?array
    {
        $query = $this->buildPortSearchQuery($label, $countryHint);

        $params = [
            'q' => $query,
            'limit' => 3,
            'lang' => 'en',
        ];

        if ($countryHint !== null) {
            $params['osm_tag'] = 'place:city';
        }

        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::withHeaders([
                'User-Agent' => 'VinstackLite/1.0 (container-tracking)',
            ])
                ->timeout(10)
                ->get('https://photon.komoot.io/api/', $params);
        } catch (RuntimeException) {
            return null;
        }

        if ($response->failed()) {
            return null;
        }

        $payload = $response->json();

        if (! is_array($payload) || empty($payload['features']) || ! is_array($payload['features'])) {
            return null;
        }

        foreach ($payload['features'] as $feature) {
            if (! is_array($feature) || ! isset($feature['geometry']['coordinates']) || ! is_array($feature['geometry']['coordinates'])) {
                continue;
            }

            $coords = $feature['geometry']['coordinates'];

            if (count($coords) < 2) {
                continue;
            }

            $lng = (float) $coords[0];
            $lat = (float) $coords[1];
            $props = is_array($feature['properties'] ?? null) ? $feature['properties'] : [];
            $countryCode = isset($props['countrycode']) ? strtoupper((string) $props['countrycode']) : null;

            if ($countryHint !== null && $countryCode !== null && $countryCode !== $countryHint) {
                continue;
            }

            $name = trim(implode(', ', array_filter([
                $props['name'] ?? null,
                $props['state'] ?? null,
                $props['country'] ?? null,
            ])));

            return [
                'name' => $name !== '' ? $name : $label,
                'lat' => $lat,
                'lng' => $lng,
                'geocoded' => true,
                'country_code' => $countryCode,
            ];
        }

        return null;
    }

    protected function buildPortSearchQuery(string $label, ?string $countryHint): string
    {
        $city = $this->cityTokenFromLabel($label);
        $countryName = $this->countryNameForHint($countryHint);

        if ($countryName !== null) {
            return $city.' port, '.$countryName;
        }

        return $label.' seaport';
    }

    protected function cityTokenFromLabel(string $label): string
    {
        $trimmed = trim($label);

        if (str_contains($trimmed, ',')) {
            return trim(explode(',', $trimmed, 2)[0]);
        }

        return $trimmed;
    }

    protected function countryNameForHint(?string $countryHint): ?string
    {
        return match ($countryHint) {
            'CA' => 'Canada',
            'US' => 'United States',
            'TR' => 'Turkey',
            'SA' => 'Saudi Arabia',
            'AE' => 'United Arab Emirates',
            'GR' => 'Greece',
            default => null,
        };
    }

    protected function throttleExternal(string $rateKey, float $minSeconds): void
    {
        $last = Cache::get($rateKey);

        if (is_numeric($last) && (microtime(true) - (float) $last) < $minSeconds) {
            usleep((int) ($minSeconds * 1_000_000));
        }

        Cache::put($rateKey, microtime(true), 60);
    }

    protected function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * @param  array<string, array{name: string, lat: float, lng: float}>  $candidates
     */
    protected function logGeocodeFailure(string $label, ?string $countryHint, array $candidates): void
    {
        if ($candidates === []) {
            return;
        }

        Log::info('port_geocode_rejected', [
            'label' => $label,
            'country_hint' => $countryHint,
            'providers' => array_map(fn (array $p) => [
                'lat' => $p['lat'],
                'lng' => $p['lng'],
                'country' => $p['country_code'] ?? null,
            ], $candidates),
        ]);
    }
}
