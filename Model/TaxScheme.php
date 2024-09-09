<?php

namespace Gw\AutoCustomerGroupEu\Model;

use GuzzleHttp\ClientFactory;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Psr7\Request;
use Gw\AutoCustomerGroup\Api\Data\TaxIdCheckResponseInterface;
use Gw\AutoCustomerGroup\Api\Data\TaxIdCheckResponseInterfaceFactory;
use Gw\AutoCustomerGroup\Api\Data\TaxSchemeInterface;
use Magento\Directory\Model\Currency;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\Information as StoreInformation;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Real EU VAT numbers (as of 11/07/2024)
 * NL - 810433941B01 - COOLBLUE B.V. - VALID
 * IE - IE8256796U - MICROSOFT IRELAND OPERATIONS LIMITED - VALID
 * IE - IE3206488LH - STRIPE PAYMENTS EUROPE LIMITED - VALID
 */
class TaxScheme  implements TaxSchemeInterface
{
    const CODE = "euvat";
    const SCHEME_CURRENCY = 'EUR';
    const array SCHEME_COUNTRIES = ['AT','BE','BG','HR','CY','CZ','DK','EE','FI','FR','DE','GR','HU','IE',
        'IT','LV','LT','LU','MT','MC','NL','PL','PT','RO','SK','SI','ES','SE'];

    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @var TaxIdCheckResponseInterfaceFactory
     */
    protected $ticrFactory;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var CurrencyFactory
     */
    public $currencyFactory;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     * @param StoreManagerInterface $storeManager
     * @param CurrencyFactory $currencyFactory
     * @param TaxIdCheckResponseInterfaceFactory $ticrFactory
     * @param ClientFactory $clientFactory
     * @param Json $serializer
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        StoreManagerInterface $storeManager,
        CurrencyFactory $currencyFactory,
        TaxIdCheckResponseInterfaceFactory $ticrFactory,
        ClientFactory $clientFactory,
        Json $serializer
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->currencyFactory = $currencyFactory;
        $this->ticrFactory = $ticrFactory;
        $this->clientFactory = $clientFactory;
        $this->serializer = $serializer;
    }

    /**
     * Get the order value, in scheme currency
     *
     * For the purposes of Scheme Threshold, the order value is defined as sum of the sale price of all
     * items sold (including any discounts)
     *
     * https://taxation-customs.ec.europa.eu/customs-4/customs-procedures-import-and-export-0/customs-procedures/customs-formalities-low-value-consignments_en
     *
     * @param Quote $quote
     * @return float
     */
    public function getOrderValue(Quote $quote): float
    {
        $orderValue = 0.0;
        foreach ($quote->getItemsCollection() as $item) {
            $orderValue += ($item->getBaseRowTotal() - $item->getBaseDiscountAmount());
        }
        return $orderValue / $this->getSchemeExchangeRate($quote->getStoreId());
    }

    /**
     * Get customer group based on Validation Result and Country of customer
     * @param string $customerCountryCode
     * @param string|null $customerPostCode
     * @param bool $taxIdValidated
     * @param float $orderValue
     * @param int|null $storeId
     * @return int|null
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getCustomerGroup(
        string $customerCountryCode,
        ?string $customerPostCode,
        bool $taxIdValidated,
        float $orderValue,
        ?int $storeId
    ): ?int {
        $merchantCountry = $this->scopeConfig->getValue(
            StoreInformation::XML_PATH_STORE_INFO_COUNTRY_CODE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        if (empty($merchantCountry)) {
            $this->logger->critical(
                "Gw/AutoCustomerGroupEu/Model/TaxScheme::getCustomerGroup() : " .
                "Merchant country not set."
            );
            return null;
        }

        $merchantPostCode = $this->scopeConfig->getValue(
            StoreInformation::XML_PATH_STORE_INFO_POSTCODE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        if (empty($merchantPostCode)) {
            // Make sure it's at least an empty string
            // We assume the merchant is not in NI if they haven't set a store postcode.
            $merchantPostCode = "";
        }

        $importThreshold = $this->getThresholdInSchemeCurrency($storeId);

        //Merchant Country is in the EU
        //Item shipped to the EU
        //Both countries the same
        //Therefore Domestic
        if (in_array($merchantCountry, self::SCHEME_COUNTRIES) &&
            in_array($customerCountryCode, self::SCHEME_COUNTRIES) &&
            $merchantCountry == $customerCountryCode) {
            return $this->scopeConfig->getValue(
                "autocustomergroup/" . self::CODE . "/domestic",
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
        }
        //Merchant Country is in the EU or NI
        //Item shipped to the EU
        //Both countries are not the same
        //Validated EU VAT Number Supplied
        //Therefore Intra EU B2B
        if ((in_array($merchantCountry, self::SCHEME_COUNTRIES) ||
                $merchantCountry == "GB" && preg_match("/^[Bb][Tt].*$/", $merchantPostCode)) &&
            in_array($customerCountryCode, self::SCHEME_COUNTRIES) &&
            $merchantCountry != $customerCountryCode &&
            $taxIdValidated) {
            return $this->scopeConfig->getValue(
                "autocustomergroup/" . self::CODE . "/intraeub2b",
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
        }
        //Merchant Country is in the EU or NI
        //Item shipped to the EU
        //Both countries are not the same
        //Validated EU VAT Number Not Supplied
        //Therefore Intra EU B2C
        if ((in_array($merchantCountry, self::SCHEME_COUNTRIES) ||
                $merchantCountry == "GB" && preg_match("/^[Bb][Tt].*$/", $merchantPostCode)) &&
            in_array($customerCountryCode, self::SCHEME_COUNTRIES) &&
            $merchantCountry != $customerCountryCode &&
            !$taxIdValidated) {
            return $this->scopeConfig->getValue(
                "autocustomergroup/" . self::CODE . "/intraeub2c",
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
        }
        //Merchant Country is not in the EU
        //Item shipped to the EU
        //Validated EU VAT Number Supplied
        //Therefore Import B2B
        if (!in_array($merchantCountry, self::SCHEME_COUNTRIES) &&
            in_array($customerCountryCode, self::SCHEME_COUNTRIES) &&
            $taxIdValidated) {
            return $this->scopeConfig->getValue(
                "autocustomergroup/" . self::CODE . "/importb2b",
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
        }
        //Merchant Country is not in the EU
        //Item shipped to the EU
        //Order value is equal or below threshold
        //Therefore Import Taxed
        if (!in_array($merchantCountry, self::SCHEME_COUNTRIES) &&
            in_array($customerCountryCode, self::SCHEME_COUNTRIES) &&
            ($orderValue <= $importThreshold)) {
            return $this->scopeConfig->getValue(
                "autocustomergroup/" . self::CODE . "/importtaxed",
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
        }
        //Merchant Country is not in the EU
        //Item shipped to the EU
        //Order value is above threshold
        //Therefore Import Unaxed
        if (!in_array($merchantCountry, self::SCHEME_COUNTRIES) &&
            in_array($customerCountryCode, self::SCHEME_COUNTRIES) &&
            ($orderValue > $importThreshold)) {
            return $this->scopeConfig->getValue(
                "autocustomergroup/" . self::CODE . "/importuntaxed",
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
        }
        return null;
    }

    /**
     * Peform validation of the VAT Number, returning a gatewayResponse object
     *
     * @param string $countryCode
     * @param string|null $taxId
     * @return TaxIdCheckResponseInterface
     */
    public function checkTaxId(
        string $countryCode,
        ?string $taxId
    ): TaxIdCheckResponseInterface {
        $taxIdCheckResponse = $this->ticrFactory->create();

        if (!in_array($countryCode, self::SCHEME_COUNTRIES)) {
            $taxIdCheckResponse->setRequestMessage(__('Unsupported country.'));
            $taxIdCheckResponse->setIsValid(false);
            $taxIdCheckResponse->setRequestSuccess(false);
            return $taxIdCheckResponse;
        }

        if ($taxId) {
            $taxId = str_replace(
                [' ', '-', $this->getCountryCodeForVatNumber($countryCode)],
                ['', '', ''],
                $taxId
            );
        }

        $taxIdCheckResponse = $this->validateFormat($taxIdCheckResponse, $taxId, $countryCode);

        if ($taxIdCheckResponse->getIsValid() && $this->scopeConfig->isSetFlag(
                "autocustomergroup/" . self::CODE . "/validate_online",
                ScopeInterface::SCOPE_STORE
            )) {
            $taxIdCheckResponse = $this->validateOnline($taxIdCheckResponse, $taxId, $countryCode);
        }

        return $taxIdCheckResponse;

    }

    /**
     * Perform offline validation of the Tax Identifier
     *
     * @param $taxIdCheckResponse
     * @param $taxId
     * @return TaxIdCheckResponseInterface
     */
    private function validateFormat($taxIdCheckResponse, $taxId, $countryCode): TaxIdCheckResponseInterface
    {
        if (($taxId === null || strlen($taxId) < 1)) {
            $taxIdCheckResponse->setRequestMessage(__('You didn\'t supply a VAT number to check.'));
            $taxIdCheckResponse->setIsValid(false);
            $taxIdCheckResponse->setRequestSuccess(true);
            return $taxIdCheckResponse;
        }
        switch ($countryCode) {
            case "AT":
                $preg = "^(AT)?U[0-9]{8}$";
                break;
            case "BE":
                $preg = "^(BE)?[0-9]{8}[0-9]{2}$";
                break;
            case "BG":
                $preg = "^(BG)?[0-9]{9,10}$";
                break;
            case "HR":
                $preg = "^^(HR)?[0-9]{11}$";
                break;
            case "CY":
                $preg = "^(CY)?[0-9]{8}L$";
                break;
            case "CZ":
                $preg = "^(CZ)?[0-9]{8,10}$";
                break;
            case "DK":
                $preg = "^(DK)?[0-9]{8}$";
                break;
            case "EE":
                $preg = "^(EE)?[0-9]{9}$";
                break;
            case "FI":
                $preg = "^(FI)?[0-9]{8}$";
                break;
            case "FR":
            case "MC":
                $preg = "^(FR)?[0-9A-Z]{2}[0-9]{9}$";
                break;
            case "DE":
                $preg = "^(DE)?[0-9]{9}$";
                break;
            case "GR":
                $preg = "^(EL|GR)?[0-9]{9}$";
                break;
            case "HU":
                $preg = "^(HU)?[0-9]{8}$";
                break;
            case "IE":
                $preg = "^(IE)?[0-9]{7}[A-Z]{1,2}$";
                break;
            case "IT":
                $preg = "^(IT)?[0-9]{11}$";
                break;
            case "LV":
                $preg = "^(LV)?[0-9]{11}$";
                break;
            case "LT":
                $preg = "^(LT)?([0-9]{9}|[0-9]{12})$";
                break;
            case "LU":
                $preg = "^(LU)?[0-9]{8}$";
                break;
            case "MT":
                $preg = "^(MT)?[0-9]{8}$";
                break;
            case "NL":
                $preg = "^(NL)?[0-9]{9}B[0-9]{2}$";
                break;
            case "PL":
                $preg = "^(PL)?[0-9]{10}$";
                break;
            case "PT":
                $preg = "^(PT)?[0-9]{9}$";
                break;
            case "RO":
                $preg = "^(RO)?[0-9]{2,10}$";
                break;
            case "SK":
                $preg = "^(SK)?[0-9]{10}$";
                break;
            case "SI":
                $preg = "^(SI)?[0-9]{8}$";
                break;
            case "ES":
                $preg = "^(ES)?[0-9A-Z][0-9]{7}[0-9A-Z]$";
                break;
            case "SE":
                $preg = "^(SE)?[0-9]{12}$";
                break;
            default:
                $preg = null;
                break;
        }
        if ($preg) {
            if (preg_match('/' . $preg . '/i', $taxId)) {
                $taxIdCheckResponse->setRequestMessage(__('VAT number is the correct format.'));
                $taxIdCheckResponse->setIsValid(true);
                $taxIdCheckResponse->setRequestSuccess(true);
            } else {
                $taxIdCheckResponse->setRequestMessage(__('VAT number is not the correct format.'));
                $taxIdCheckResponse->setIsValid(false);
                $taxIdCheckResponse->setRequestSuccess(true);
            }
        } else {
            $taxIdCheckResponse->setRequestMessage(__('Unsupported country.'));
            $taxIdCheckResponse->setIsValid(false);
            $taxIdCheckResponse->setRequestSuccess(false);
        }
        return $taxIdCheckResponse;
    }

    /**
     * Perform online validation of the Tax Identifier
     *
     * @param $taxIdCheckResponse
     * @param $taxId
     * @return TaxIdCheckResponseInterface
     */
    private function validateOnline($taxIdCheckResponse, $taxId, $countryCode): TaxIdCheckResponseInterface
    {
        try {
            $body = [];
            $body['countryCode'] = $countryCode;
            $body['vatNumber'] = $taxId;

            $requesterCountryCode = $this->scopeConfig->getValue(
                "autocustomergroup/" . self::CODE . "/viesregistrationcountry",
                ScopeInterface::SCOPE_STORE
            );
            $requesterVatNumber = $this->scopeConfig->getValue(
                "autocustomergroup/" . self::CODE . "/viesregistrationnumber",
                ScopeInterface::SCOPE_STORE
            );

            if (!empty($requesterCountryCode) && !empty($requesterVatNumber)) {
                $requesterVatNumber = str_replace(
                    [' ', '-', $this->getCountryCodeForVatNumber($requesterCountryCode)],
                    ['', '', ''],
                    $requesterVatNumber
                );
                $body['requesterMemberStateCode'] = $requesterCountryCode;
                $body['requesterNumber'] = $requesterVatNumber;
            }

            $client = $this->clientFactory->create();
            $response = $client->send(
                new Request(
                    "POST",
                    "https://ec.europa.eu/taxation_customs/vies/rest-api/check-vat-number",
                    [
                        'Content-Type' => "application/json",
                        'Accept' => "application/json"
                    ],
                    $this->serializer->serialize($body)
                )
            );
            $responseBody = $response->getBody();
            $vatRegistration = $this->serializer->unserialize($responseBody->getContents());
            if (isset($vatRegistration['actionSucceeded']) && $vatRegistration['actionSucceeded'] == false) {
                $taxIdCheckResponse->setIsValid(false);
                $taxIdCheckResponse->setRequestSuccess(false);
                $taxIdCheckResponse->setRequestMessage(__('There was an error checking the VAT number.'));
            } else {
                $taxIdCheckResponse->setIsValid($vatRegistration['valid']);
                $taxIdCheckResponse->setRequestSuccess(true);
                $taxIdCheckResponse->setRequestDate($vatRegistration['requestDate']);
                $taxIdCheckResponse->setRequestIdentifier($vatRegistration['requestIdentifier']);
                if ($taxIdCheckResponse->getIsValid()) {
                    $taxIdCheckResponse->setRequestMessage(__('VAT Number validated with VIES.'));
                } else {
                    $taxIdCheckResponse->setRequestMessage(__('Please enter a valid VAT number including country code.'));
                }
            }
        } catch (BadResponseException $e) {
            switch ($e->getCode()) {
                case 404:
                    $taxIdCheckResponse->setIsValid(false);
                    $taxIdCheckResponse->setRequestSuccess(true);
                    $taxIdCheckResponse->setRequestMessage(__('Please enter a valid VAT number.'));
                    break;
                default:
                    $taxIdCheckResponse->setIsValid(false);
                    $taxIdCheckResponse->setRequestSuccess(false);
                    $taxIdCheckResponse->setRequestMessage(__('There was an error checking the VAT number.'));
                    $this->logger->error(
                        "Gw/AutoCustomerGroup/Model/TaxSchemes/EuVat::checkTaxId() : EuVat Error received from " .
                        "VIES. " . $e->getCode()
                    );
                    break;
            }
        }
        return $taxIdCheckResponse;
    }

    /**
     * Returns the country code to use in the VAT number which is not always the same as the normal country code
     *
     * @param string $countryCode
     * @return string
     */
    private function getCountryCodeForVatNumber(string $countryCode): string
    {
        // Greece uses a different code for VAT numbers then its country code
        // See: http://ec.europa.eu/taxation_customs/vies/faq.html#item_11
        // And https://en.wikipedia.org/wiki/VAT_identification_number:
        // "The full identifier starts with an ISO 3166-1 alpha-2 (2 letters) country code
        // (except for Greece, which uses the ISO 639-1 language code EL for the Greek language,
        // instead of its ISO 3166-1 alpha-2 country code GR)"

        return $countryCode === 'GR' ? 'EL' : $countryCode;
    }

    /**
     * Get the scheme name
     *
     * @return string
     */
    public function getSchemeName(): string
    {
        return __("European Union VAT OSS/IOSS Scheme");
    }

    /**
     * Get the scheme code
     *
     * @return string
     */
    public function getSchemeId(): string
    {
        return self::CODE;
    }

    /**
     * @param int|null $storeId
     * @return string|null
     */
    public function getFrontEndPrompt(?int $storeId): ?string
    {
        return $this->scopeConfig->getValue(
            "autocustomergroup/" . self::CODE . "/frontendprompt",
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @return string
     */
    public function getSchemeCurrencyCode(): string
    {
        return self::SCHEME_CURRENCY;
    }

    /**
     * @param int|null $storeId
     * @return float
     */
    public function getThresholdInSchemeCurrency(?int $storeId): float
    {
        return $this->scopeConfig->getValue(
            "autocustomergroup/" . self::CODE . "/importthreshold",
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param int|null $storeId
     * @return string|null
     */
    public function getSchemeRegistrationNumber(?int $storeId): ?string
    {
        return $this->scopeConfig->getValue(
            "autocustomergroup/" . self::CODE . "/registrationnumber",
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @return array
     */
    public function getSchemeCountries(): array
    {
        return self::SCHEME_COUNTRIES;
    }

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabled(?int $storeId): bool
    {
        return $this->scopeConfig->isSetFlag(
            "autocustomergroup/" . self::CODE . "/enabled",
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param int|null $storeId
     * @return float
     */
    public function getSchemeExchangeRate(?int $storeId): float
    {
        if ($this->scopeConfig->isSetFlag(
            "autocustomergroup/" . self::CODE . '/usemagentoexchangerate',
            ScopeInterface::SCOPE_STORE,
            $storeId
        )) {
            $websiteBaseCurrency = $this->scopeConfig->getValue(
                Currency::XML_PATH_CURRENCY_BASE,
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
            $exchangerate = $this->currencyFactory
                ->create()
                ->load($this->getSchemeCurrencyCode())
                ->getAnyRate($websiteBaseCurrency);
            if (!$exchangerate) {
                $this->logger->critical(
                    "Gw/AutoCustomerGroupEu/Model/TaxScheme::getSchemeExchangeRate() : " .
                    "No Magento Exchange Rate configured for " . self::SCHEME_CURRENCY . " to " .
                    $websiteBaseCurrency . ". Using 1.0"
                );
                $exchangerate = 1.0;
            }
            return (float)$exchangerate;
        }
        return (float)$this->scopeConfig->getValue(
            "autocustomergroup/" . self::CODE . "/exchangerate",
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
