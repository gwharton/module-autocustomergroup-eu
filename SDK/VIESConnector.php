<?php
namespace Gw\AutoCustomerGroupEu\SDK;

use Gw\AutoCustomerGroupEu\SDK\Requests\CheckVATNumberRequest;
use Gw\AutoCustomerGroupEu\SDK\Dto\CheckVATNumberRequest as CheckVATNumberRequestDto;
use Saloon\Http\Connector;
use Saloon\Http\Response;

class VIESConnector extends Connector
{
    public function resolveBaseUrl(): string
    {
        return ("https://ec.europa.eu/taxation_customs/vies/rest-api");
    }

    protected function defaultHeaders(): array
    {
        return [
            'user-agent' => 'GrahamWhartonAutoCustomerGroupEU/1.0 graham@lubefinder.com'
        ];
    }

    public function checkVATNumber(
        CheckVATNumberRequestDto $checkVATNumberRequestDto,
    ): Response {
        $request = new CheckVATNumberRequest(
            $checkVATNumberRequestDto
        );
        return $this->send($request);
    }
}
