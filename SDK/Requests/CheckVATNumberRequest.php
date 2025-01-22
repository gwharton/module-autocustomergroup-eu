<?php
namespace Gw\AutoCustomerGroupEu\SDK\Requests;

use Exception;
use Gw\AutoCustomerGroupEu\SDK\Dto\CheckVATNumberRequest as CheckVATNumberRequestDto;
use Gw\AutoCustomerGroupEu\SDK\Dto\CheckVATNumberResponse;
use Gw\AutoCustomerGroupEu\SDK\Dto\ErrorResponse;
use JsonMapper;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

class CheckVATNumberRequest extends BaseRequest implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    /**
     * @param CheckVATNumberRequestDto $checkVATNumberRequestDto
     */
    public function __construct(
        public CheckVATNumberRequestDto $checkVATNumberRequestDto
    ) {}

    public function resolveEndpoint(): string
    {
        return "/check-vat-number";
    }

    public function defaultBody(): array
    {
        return $this->toArray($this->checkVATNumberRequestDto);
    }

    public function createDtoFromResponse(Response $response): CheckVATNumberResponse|ErrorResponse
    {
        $status = $response->status();
        $responseClass = match ($status) {
            200 => CheckVATNumberResponse::class,
            400, 403, 500 => ErrorResponse::class,
            default => throw new Exception("Unhandled response status: {$status}")
        };
        return (new JsonMapper)->map(json_decode($response->body()), $responseClass);
    }
}
