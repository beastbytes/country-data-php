<?php
/**
 * @copyright Copyright © 2023 BeastBytes - All rights reserved
 * @license BSD 3-Clause
 */

declare(strict_types=1);

namespace BeastBytes\Country\PHP\Tests;

use BeastBytes\Country\PHP\CountryData;
use Generator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CountryDataTest extends TestCase
{
    private static array $data = [];
    private static CountryData $testClass;

    private const ARRAY_ARG = [
        'AQ' => [
            'name' => 'Antarctica',
            'alpha2' => 'AQ',
            'alpha3' => 'ATA',
            'numeric' => '010',
            'tld' => 'aq',
            'idc' => '672',
            'timezone' => '-12,+12',
            'nameFormat' => '',
            'addressFormat' => ''
        ],
        'EU' => [
            'name' => 'European Union',
            'alpha2' => 'EU',
            'alpha3' => 'ZZA',
            'numeric' => '990',
            'tld' => 'eu',
            'idc' => '',
            'timezone' => '',
            'nameFormat' => '',
            'addressFormat' => ''
        ],
        'UN' => [
            'name' => 'United Nations',
            'alpha2' => 'UN',
            'alpha3' => 'ZZZ',
            'numeric' => '999',
            'tld' => '',
            'idc' => '',
            'timezone' => '',
            'nameFormat' => '',
            'addressFormat' => ''
        ],
    ];

    /** @var string[] Additional flags */
    private const ISO_3166_2_FLAGS = ['BQ-BO', 'BQ-SA', 'BQ-SE', 'GB-ENG', 'GB-SCT', 'GB-WLS'];

    /** @var string[] Countries without a flag */
    private const NO_FLAG = ['EH'];

    #[BeforeClass]
    public static function init(): void
    {
        self::$data = require dirname(__DIR__) . '/data/data.php';
        self::$testClass = new CountryData();
    }

    #[DataProvider('constructorArgumentProvider')]
    public function test_constructor(mixed $arg, int $count): void
    {
        $class = new CountryData($arg);
        $this->assertCount($count, $class->getCountries());
    }

    #[DataProvider('badConstructorArgumentProvider')]
    public function test_bad_constructor(string $data): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(CountryData::INVALID_DATA_EXCEPTION);
        $class = new CountryData(__DIR__ . "/data/$data.php");
    }

    public function test_getting_countries(): void
    {
        $this->assertCount(count(self::$data), self::$testClass->getCountries());
    }

    #[DataProvider('goodCountriesProvider')]
    public function test_has_country(string $country): void
    {
        $this->assertTrue(self::$testClass->hasCountry($country));
    }

    #[DataProvider('badCountriesProvider')]
    public function test_does_not_have_country(string $country): void
    {
        $this->assertFalse(self::$testClass->hasCountry($country));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(strtr(CountryData::INVALID_COUNTRY_EXCEPTION, ['{country}' => $country]));
        self::$testClass->getCountry($country);
    }

    #[DataProvider('getterProvider')]
    public function test_getters(string $country, string $getter): void
    {
        $this->assertSame(
            self::$data[$country][lcfirst(substr($getter, 3))],
            self::$testClass->$getter($country)
        );
    }

    #[DataProvider('goodCountriesProvider')]
    public function test_country_data(string $country): void
    {
        $this->assertSame(
            self::$data[$country],
            self::$testClass->getCountry($country)
        );
    }

    #[DataProvider('flagsProvider')]
    public function test_flags(string $country): void
    {
        $this->assertSame(
            file_get_contents(dirname(__DIR__) . "/data/flags/$country.svg"),
            self::$testClass->getFlag($country)
        );
    }

    public static function badConstructorArgumentProvider(): Generator
    {
        foreach (
            [
                'null',
                'string'
            ] as $name
        ) {
            yield $name => [$name];
        }
    }

    public static function constructorArgumentProvider(): Generator
    {
        $data = require dirname(__DIR__) . '/data/data.php';

        foreach ([
            'NULL' => ['arg' => null, 'count' => count($data)],
            'Path' => ['arg' => dirname(__DIR__) . '/data/data.php', 'count' => count($data)],
            'Array' => ['arg' => self::ARRAY_ARG, 'count' => count(self::ARRAY_ARG)]
        ] as $name => $value) {
            yield $name => $value;
        }
    }

    public static function flagsProvider(): Generator
    {
        $data = require dirname(__DIR__) . '/data/data.php';
        $flags = array_keys($data);
        $flags =  array_diff($flags, self::NO_FLAG);

        foreach ($flags as $country) {
            yield $country => [$country];
        }

        foreach (self::ISO_3166_2_FLAGS as $country) {
            yield $country => [$country];
        }
    }

    public static function goodCountriesProvider(): Generator
    {
        $data = require dirname(__DIR__) . '/data/data.php';

        foreach (array_keys($data) as $country) {
            yield $country => [$country];
        }
    }

    public static function badCountriesProvider(): Generator
    {
        foreach ([
            'non-existent code' => ['XX'],
            'alpha-3 code' => ['GBR'],
            'too short' => ['G'],
            'too long' => ['GBRT'],
            'number string' => ['12']
        ] as $name => $value) {
            yield $name => $value;
        }
    }

    public static function getterProvider(): Generator
    {
        $data = require dirname(__DIR__) . '/data/data.php';
        $methods = get_class_methods(CountryData::class);

        foreach (array_keys($data) as $country) {
            foreach ($methods as $method) {
                if (
                    !in_array($method, ['getCountries', 'getCountry', 'getFlag'], true)
                    && str_starts_with($method, 'get')
                ) {
                    yield $country . ' - ' . $method => [
                        'country' => $country,
                        'getter' => $method
                    ];
                }
            }
        }
    }
}
