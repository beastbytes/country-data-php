<?php
/**
 * @copyright Copyright © 2023 BeastBytes - All rights reserved
 * @license BSD 3-Clause
 */

declare(strict_types=1);

namespace BeastBytes\Country\PHP;

use BeastBytes\Country\CountryDataInterface;
use InvalidArgumentException;

class CountryData implements CountryDataInterface
{
    public const INVALID_COUNTRY_EXECPTION = 'Country {country} not found';
    public const INVALID_DATA_EXECPTION = '`$data` must be an array of country data, a path to a file that returns an array of country data, or `null` to use local data';

    public function __construct(private array|string|null $data = null)
    {
        if ($this->data === null) {
            $this->data = require '../data/data.php';
        } elseif (is_string($this->data)) {
            $this->data = require $this->data;
        }

        if (!is_array($this->data)) {
            throw new InvalidArgumentException(self::INVALID_DATA_EXECPTION);
        }
    }

    public function getCountries(): array
    {
        return array_keys($this->data);
    }

    public function hasCountry(string $country): bool
    {
        return array_key_exists($country, $this->data);
    }

    public function formatAddress(array $address, string $country): string
    {
        $result = strtr($this->getAddressFormat($country), $address);
        $result = preg_replace('/\^(.+)\^/', '\U$1\E', $result);
        return preg_replace(['/\n\s+/', '/\s+/', '/\n+/'], ["\n", ' ', "\n"], $result);
    }

    public function formatName(array $name, string $country): string
    {
        $result = strtr($this->getNameFormat($country), $name);
        return preg_replace('/\s{+}/', ' ', $result);
    }

    public function getAddressFormat(string $country): string
    {
        $this->checkCountry($country);
        return $this->data[$country]['addressFormat'];
    }

    public function getAlpha3(string $country): string
    {
        $this->checkCountry($country);
        return $this->data[$country]['alpha3'];
    }

    public function getCountry(string $country): array
    {
        $this->checkCountry($country);
        return $this->data[$country];
    }

    public function getFlag(string $country): string
    {
        $country = strtoupper($country);
        $this->checkCountry($country, true);
        return file_get_contents("../data/flags/$country.svg");
    }

    public function getIdc(string $country): string
    {
        $this->checkCountry($country);
        return $this->data[$country]['idc'];
    }

    public function getName(string $country): string
    {
        $this->checkCountry($country);
        return $this->data[$country]['name'];
    }

    public function getNameFormat(string $country): string
    {
        $this->checkCountry($country);
        return $this->data[$country]['nameFormat'];
    }

    public function getNumericCode(string $country): string
    {
        $this->checkCountry($country);
        return $this->data[$country]['numeric'];
    }

    public function getTimezone(string $country): string
    {
        $this->checkCountry($country);
        return $this->data[$country]['timezone'];
    }

    public function getTld(string $country): string
    {
        $this->checkCountry($country);
        return $this->data[$country]['tld'];
    }

    private function checkCountry(string $country, $isFlag = false): void
    {
        if (
            ($isFlag && !file_exists("../data/flags/$country.svg"))
            || !$this->hasCountry($country)
        ) {
            throw new InvalidArgumentException(strtr(self::INVALID_COUNTRY_EXECPTION, ['{country}' => $country]));
        }
    }
}
