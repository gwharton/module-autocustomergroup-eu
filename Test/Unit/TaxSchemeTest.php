<?php

namespace Gw\AutoCustomerGroupEu\Test\Unit;

use GuzzleHttp\ClientFactory;
use Gw\AutoCustomerGroup\Model\TaxSchemeHelper;
use Gw\AutoCustomerGroupEu\Model\TaxScheme;
use Gw\AutoCustomerGroup\Api\Data\TaxIdCheckResponseInterfaceFactory;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class TaxSchemeTest extends TestCase
{
    /**
     * @var TaxScheme
     */
    private $model;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfigMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManagerMock;

    /**
     * @var CurrencyFactory|MockObject
     */
    private $currencyFactoryMock;

    /**
     * @var TaxIdCheckResponseInterfaceFactory|MockObject
     */
    private $taxIdCheckResponseInterfaceFactoryMock;

    /**
     * @var ClientFactory|MockObject
     */
    private $clientFactoryMock;

    /**
     * @var Json|MockObject
     */
    private $jsonMock;

    /**
     * @var TaxSchemeHelper|MockObject
     */
    private $helperMock;

    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->currencyFactoryMock = $this->getMockBuilder(CurrencyFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->taxIdCheckResponseInterfaceFactoryMock = $this->getMockBuilder(TaxIdCheckResponseInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->clientFactoryMock = $this->getMockBuilder(ClientFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->jsonMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->helperMock = $this->getMockBuilder(TaxSchemeHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = new TaxScheme(
            $this->scopeConfigMock,
            $this->loggerMock,
            $this->storeManagerMock,
            $this->currencyFactoryMock,
            $this->taxIdCheckResponseInterfaceFactoryMock,
            $this->clientFactoryMock,
            $this->jsonMock,
            $this->helperMock
        );
    }

    public function testGetSchemeName(): void
    {
        $schemeName = $this->model->getSchemeName();
        $this->assertIsString($schemeName);
        $this->assertGreaterThan(0, strlen($schemeName));
    }
}

