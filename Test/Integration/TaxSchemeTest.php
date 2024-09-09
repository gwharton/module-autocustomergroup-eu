<?php

namespace Gw\AutoCustomerGroupEu\Test\Integration;

use Gw\AutoCustomerGroupEu\Model\TaxScheme;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\GuestCartManagementInterface;
use Magento\Quote\Api\GuestCartRepositoryInterface;
use Magento\Store\Model\Information as StoreInformation;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 * @magentoAppArea frontend
 */
class TaxSchemeTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var TaxScheme
     */
    private $taxScheme;

    /**
     * @var ReinitableConfigInterface
     */
    private $config;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ProductFactory
     */
    private $productFactory;

    /**
     * @var GuestCartManagementInterface
     */
    private $guestCartManagement;

    /**
     * @var GuestCartRepositoryInterface
     */
    private $guestCartRepository;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager = Bootstrap::getObjectManager();
        $this->taxScheme = $this->objectManager->get(TaxScheme::class);
        $this->config = $this->objectManager->get(ReinitableConfigInterface::class);
        $this->storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $this->productFactory = $this->objectManager->get(ProductFactory::class);
        $this->guestCartManagement = $this->objectManager->get(GuestCartManagementInterface::class);
        $this->guestCartRepository = $this->objectManager->get(GuestCartRepositoryInterface::class);
        $this->quoteRepository = $this->objectManager->get(CartRepositoryInterface::class);
        $this->productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
    }

    /**
     * @magentoAdminConfigFixture current_store currency/options/default GBP
     * @magentoAdminConfigFixture current_store currency/options/base GBP
     * @magentoConfigFixture current_store autocustomergroup/euvat/usemagentoexchangerate 0
     * @magentoConfigFixture current_store autocustomergroup/euvat/exchangerate 0.8288
     * @dataProvider getOrderValueDataProvider
     */
    public function testGetOrderValue(
        $qty1,
        $price1,
        $qty2,
        $price2,
        $expectedValue
    ): void {
        $product1 = $this->productFactory->create();
        $product1->setTypeId('simple')
            ->setId(1)
            ->setAttributeSetId(4)
            ->setWebsiteIds([1])
            ->setName('Simple Product 1')
            ->setSku('simple1')
            ->setPrice($price1)
            ->setVisibility(Visibility::VISIBILITY_BOTH)
            ->setStatus(Status::STATUS_ENABLED)
            ->setStockData(['use_config_manage_stock' => 0]);
        $this->productRepository->save($product1);
        $product2 = $this->productFactory->create();
        $product2->setTypeId('simple')
            ->setId(2)
            ->setAttributeSetId(4)
            ->setWebsiteIds([1])
            ->setName('Simple Product 2')
            ->setSku('simple2')
            ->setPrice($price2)
            ->setVisibility(Visibility::VISIBILITY_BOTH)
            ->setStatus(Status::STATUS_ENABLED)
            ->setStockData(['use_config_manage_stock' => 0]);
        $this->productRepository->save($product2);
        $maskedCartId = $this->guestCartManagement->createEmptyCart();
        $quote = $this->guestCartRepository->get($maskedCartId);
        $quote->addProduct($product1, $qty1);
        $quote->addProduct($product2, $qty2);
        $this->quoteRepository->save($quote);
        $result = $this->taxScheme->getOrderValue(
            $quote
        );
        $this->assertEqualsWithDelta($expectedValue, $result, 0.009);
    }

    /**
     * Remember for EU, it is the sum of the item prices that counts.
     *
     * @return array
     */
    public function getOrderValueDataProvider(): array
    {
        // Quantity 1
        // Base Price 1
        // Quantity 2
        // Base Price 2
        // Expected Order Value Scheme Currency
        return [
            [1, 99.99, 1, 0.99, 121.83],   // 100.98GBP in EUR
            [1, 100.00, 3, 0.10, 121.02],  // 100.30GBP in EUR
            [2, 100.00, 2, 100.00, 482.62],// 400.00GBP in EUR
            [7, 25.50, 1, 100, 336.03]     // 278.50GBP in EUR
        ];
    }

    /**
     * @magentoConfigFixture current_store autocustomergroup/euvat/domestic 1
     * @magentoConfigFixture current_store autocustomergroup/euvat/intraeub2b 2
     * @magentoConfigFixture current_store autocustomergroup/euvat/intraeub2c 3
     * @magentoConfigFixture current_store autocustomergroup/euvat/importb2b 4
     * @magentoConfigFixture current_store autocustomergroup/euvat/importtaxed 5
     * @magentoConfigFixture current_store autocustomergroup/euvat/importuntaxed 6
     * @magentoConfigFixture current_store autocustomergroup/euvat/importthreshold 150
     * @dataProvider getCustomerGroupDataProvider
     */
    public function testGetCustomerGroup(
        $merchantCountryCode,
        $merchantPostCode,
        $customerCountryCode,
        $customerPostCode,
        $taxIdValidated,
        $orderValue,
        $expectedGroup
    ): void {
        $storeId = $this->storeManager->getStore()->getId();
        $this->config->setValue(
            StoreInformation::XML_PATH_STORE_INFO_COUNTRY_CODE,
            $merchantCountryCode,
            ScopeInterface::SCOPE_STORE
        );
        $this->config->setValue(
            StoreInformation::XML_PATH_STORE_INFO_POSTCODE,
            $merchantPostCode,
            ScopeInterface::SCOPE_STORE
        );
        $result = $this->taxScheme->getCustomerGroup(
            $customerCountryCode,
            $customerPostCode,
            $taxIdValidated,
            $orderValue,
            $storeId
        );
        $this->assertEquals($expectedGroup, $result);
    }

    /**
     * @return array
     */
    public function getCustomerGroupDataProvider(): array
    {
        //Merchant Country Code
        //Merchant Post Code
        //Customer Country Code
        //Customer Post Code
        //taxIdValidated
        //OrderValue
        //Expected Group
        return [
            // IE to IE, value doesn't matter, VAT number status doesn't matter - Domestic
            ['IE', null, 'IE', null, false, 149, 1],
            ['IE', null, 'IE', null, true, 151, 1],
            ['IE', null, 'IE', null, false, 149, 1],
            ['IE', null, 'IE', null, false, 149, 1],
            ['IE', null, 'IE', null, true, 149, 1],
            ['IE', null, 'IE', null, false, 151, 1],
            ['IE', null, 'IE', null, false, 151, 1],
            // EU to EU, value doesn't matter, Valid VAT number - IntraEU B2B
            ['DE', null, 'FI', null, true, 149, 2],
            ['IE', null, 'ES', null, true, 151, 2],
            ['SK', null, 'IE', null, true, 149, 2],
            ['GB', 'BT1 1AA', 'PT', null, true, 149, 2],
            ['GR', null, 'IE', null, true, 149, 2],
            ['GB', 'BT1 1AA', 'ES', null, true, 151, 2],
            ['MT', null, 'IE', null, true, 151, 2],
            // EU to EU, value doesn't matter, No or invalid VAT number - IntraEU B2C
            ['DE', null, 'FI', null, false, 149, 3],
            ['IE', null, 'ES', null, false, 151, 3],
            ['SK', null, 'IE', null, false, 149, 3],
            ['GB', 'BT1 1AA', 'PT', null, false, 149, 3],
            ['GR', null, 'IE', null, false, 149, 3],
            ['GB', 'BT1 1AA', 'ES', null, false, 151, 3],
            ['MT', null, 'IE', null, false, 151, 3],
            // Import into EU, value doesn't matter, valid VAT number - Import B2B
            ['US', null, 'ES', null, true, 149, 4],
            ['US', null, 'FI', null, true, 151, 4],
            // Import into EU, value below threshold, Should only be B2C at this point - Import Taxed
            ['BR', null, 'FI', null, false, 149, 5],
            ['BR', null, 'ES', null, false, 149, 5],
            // Import into EU, value above threshold, Should only be B2C at this point - Import Untaxed
            ['NZ', null, 'ES', null, false, 151, 6],
            ['NZ', null, 'FI', null, false, 151, 6],
        ];
    }

    /**
     * @magentoConfigFixture current_store autocustomergroup/euvat/registrationnumber IE8256796U
     * @magentoConfigFixture current_store autocustomergroup/euvat/environment sandbox
     * @magentoConfigFixture current_store autocustomergroup/euvat/validate_online 1
     * @dataProvider checkTaxIdDataProviderOnline
     */
    public function testCheckTaxIdOnline(
        $countryCode,
        $taxId,
        $isValid
    ): void {
        $result = $this->taxScheme->checkTaxId(
            $countryCode,
            $taxId
        );
        $this->assertEquals($isValid, $result->getIsValid());
    }

    /**
     * @return array
     */
    public function checkTaxIdDataProviderOnline(): array
    {
        //Country code
        //Tax Id
        //IsValid
        return [
            ['DE', '',                  false],
            ['PO', null,                false],
            ['NL', '810433941B01',      true], // Valid VAT
            ['IE', 'IE8256796U',        true], // Valid VAT
            ['IE', 'IE3206488LH',       true], // Valid VAT
            ['BE', 'reghewrhwh',        false],
            ['NO', '43643634',          false],
            ['NO', '3y534673333y',      false],
            ['NO', 'AB6564764586587',   false],
            ['US', 'IE8256796U',        false], // Unsupported Country, despite valid VAT Number
            ['PO', 'th',                false],
            ['NO', '786176152',         false], // Unsupported Country
        ];
    }

    /**
     * @magentoConfigFixture current_store autocustomergroup/euvat/registrationnumber IE8256796U
     * @magentoConfigFixture current_store autocustomergroup/euvat/environment sandbox
     * @dataProvider checkTaxIdDataProviderOffline
     */
    public function testCheckTaxIdOffline(
        $countryCode,
        $taxId,
        $isValid
    ): void {
        $result = $this->taxScheme->checkTaxId(
            $countryCode,
            $taxId
        );
        $this->assertEquals($isValid, $result->getIsValid());
    }

    /**
     * @return array
     */
    public function checkTaxIdDataProviderOffline(): array
    {
        //Country code
        //Tax Id
        //IsValid
        return [
            ['AT', 'ATU99999999',       true],
            ['BE', 'BE9999999999',      true],
            ['BG', 'BG999999999',       true],
            ['HR', 'HR99999999999',     true],
            ['CY', 'CY99999999L',       true],
            ['DK', 'DK99999999',        true],
            ['EE', 'EE999999999',       true],
            ['FI', 'FI99999999',        true],
            ['FR', 'FR99999999999',     true],
            ['GR', 'EL999999999',       true],
            ['HU', 'HU99999999',        true],
            ['IE', 'IE9999999X',        true],
            ['IE', 'IE9999999XX',       true],
            ['IT', 'IT99999999999',     true],
            ['LV', 'LV99999999999',     true],
            ['LT', 'LT999999999',       true],
            ['LT', 'LT999999999999',    true],
            ['LU', 'LU99999999',        true],
            ['MT', 'MT99999999',        true],
            ['PL', 'PL9999999999',      true],
            ['PT', 'PT999999999',       true],
            ['RO', 'RO99',              true],
            ['RO', 'RO999',             true],
            ['RO', 'RO9999',            true],
            ['RO', 'RO99999',           true],
            ['RO', 'RO999999',          true],
            ['RO', 'RO9999999',         true],
            ['RO', 'RO99999999',        true],
            ['RO', 'RO999999999',       true],
            ['RO', 'RO9999999999',      true],
            ['SI', 'SI99999999',        true],
            ['ES', 'ESX99999999',       true],
            ['ES', 'ESX9999999X',       true],
            ['ES', 'ES99999999X',       true],
            ['SE', 'SE999999999999',    true],
            ['AT', 'ATU9999999',       false],
            ['BE', 'BE999999999',      false],
            ['BG', 'BG99999999',       false],
            ['HR', 'HR9999999999',     false],
            ['CY', 'CY99999999',       false],
            ['DK', 'DK9999999',        false],
            ['EE', 'EE99999999',       false],
            ['FI', 'FI9999999',        false],
            ['FR', 'FR9999999999',     false],
            ['GR', 'EL99999999',       false],
            ['HU', 'HU9999999',        false],
            ['IE', 'IE9999999',        false],
            ['IE', 'IE999999X',        false],
            ['IT', 'IT9999999999',     false],
            ['LV', 'LV9999999999',     false],
            ['LT', 'LT99999999',       false],
            ['LT', 'LT99999999999',    false],
            ['LU', 'LU9999999',        false],
            ['MT', 'MT9999999',        false],
            ['PL', 'PL999999999',      false],
            ['PT', 'PT99999999',       false],
            ['RO', 'RO9',              false],
            ['SI', 'SI9999999',        false],
            ['ES', 'ESX9999999',       false],
            ['ES', 'ESX9999999',       false],
            ['ES', 'ES99999999',       false],
            ['SE', 'SE99999999999',    false]
        ];
    }
}

