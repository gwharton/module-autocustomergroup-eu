<?php
namespace Gw\AutoCustomerGroupEu\SDK\Dto;

class CheckVATNumberResponse
{
    /**
     * @param string $countryCode
     * @param string $vatNumber
     * @param string $requestDate
     * @param bool $valid
     * @param string|null $requestIdentifier
     * @param string|null $name
     * @param string|null $address
     * @param string|null $traderName
     * @param string|null $traderStreet
     * @param string|null $traderPostalCode
     * @param string|null $traderCity
     * @param string|null $traderCompanyType
     * @param string|null $traderNameMatch
     * @param string|null $traderStreetMatch
     * @param string|null $traderPostalCodeMatch
     * @param string|null $traderCityMatch
     */
    public function __construct(
        public string $countryCode,
        public string $vatNumber,
        public string $requestDate,
        public bool $valid,
        public ?string $requestIdentifier = null,
        public ?string $name = null,
        public ?string $address = null,
        public ?string $traderName = null,
        public ?string $traderStreet = null,
        public ?string $traderPostalCode = null,
        public ?string $traderCity = null,
        public ?string $traderCompanyType = null,
        public ?string $traderNameMatch = null,
        public ?string $traderStreetMatch = null,
        public ?string $traderPostalCodeMatch = null,
        public ?string $traderCityMatch = null
    ) {}
}
