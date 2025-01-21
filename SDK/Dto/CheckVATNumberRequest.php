<?php
namespace Gw\AutoCustomerGroupEu\SDK\Dto;

final class CheckVATNumberRequest
{
    /**
     * @param string $countryCode
     * @param string $vatNumber
     * @param string|null $requesterMemberStateCode
     * @param string|null $requesterNumber
     * @param string|null $traderName
     * @param string|null $traderStreet
     * @param string|null $traderPostalCode
     * @param string|null $traderCity
     * @param string|null $traderCompanyType
     */
    public function __construct(
        public string $countryCode,
        public string $vatNumber,
        public ?string $requesterMemberStateCode = null,
        public ?string $requesterNumber = null,
        public ?string $traderName = null,
        public ?string $traderStreet = null,
        public ?string $traderPostalCode = null,
        public ?string $traderCity = null,
        public ?string $traderCompanyType = null
    ) {}
}
