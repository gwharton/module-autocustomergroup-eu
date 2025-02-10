<?php
namespace Gw\AutoCustomerGroupEu\SDK\Dto;

class CheckVATNumberResponse
{
    /**
     * @param string|null $countryCode
     * @param string|null $vatNumber
     * @param string|null $requestDate
     * @param bool|null $valid
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
     * @param bool|null $actionSucceed
     * @param \Gw\AutoCustomerGroupEu\SDK\Dto\Error[] $errorWrappers
     */
    public function __construct(
        public ?string $countryCode = null,
        public ?string $vatNumber = null,
        public ?string $requestDate = null,
        public ?bool $valid = null,
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
        public ?string $traderCityMatch = null,
        public ?bool $actionSucceed = null,
        public ?array $errorWrappers = []
    ) {}
}
