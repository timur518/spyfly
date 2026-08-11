<?php

namespace Database\Seeders;

use App\Models\Airport;
use Illuminate\Database\Seeder;
use RuntimeException;

class AirportSeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = '/public/data/airports.json';

        if (! is_file($jsonPath)) {
            throw new RuntimeException("Airports source file not found: {$jsonPath}");
        }

        /** @var array<int, array{code:string,label:string,city_code?:string,country_code?:string}> $airports */
        $airports = json_decode(file_get_contents($jsonPath), true, 512, JSON_THROW_ON_ERROR);

        $records = array_map(static function (array $airport): array {
            [$city, $name, $sourceLabel] = self::parseLabel($airport['label']);

            $additionalNames = array_values(array_filter(array_unique([
                $sourceLabel,
                $airport['code'],
                $airport['city_code'] ?? null,
                $airport['country_code'] ?? null,
                $city,
                $name,
            ])));

            return [
                'city' => $city,
                'name' => $name,
                'iata_code' => $airport['code'],
                'additional_names' => implode("\n", $additionalNames),
                'is_popular_destination' => false,
                'is_active' => true,
            ];
        }, $airports);

        Airport::query()->upsert(
            $records,
            ['iata_code'],
            ['city', 'name', 'additional_names', 'is_popular_destination', 'is_active']
        );
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private static function parseLabel(string $label): array
    {
        $normalizedLabel = preg_replace('/\p{Cf}/u', '', $label) ?? $label;

        if (preg_match('/^(?<city>.+?)\s+—\s+(?<name>.+?)(?:\s+\([A-Z0-9]{3}\))?$/u', $normalizedLabel, $matches) !== 1) {
            return [$normalizedLabel, $normalizedLabel, $label];
        }

        $city = trim($matches['city']);
        $name = trim($matches['name'] ?? $city);

        return [$city, $name, $label];
    }
}
