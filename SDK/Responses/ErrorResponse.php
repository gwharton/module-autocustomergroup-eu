<?php
namespace Gw\AutoCustomerGroupEu\SDK\Responses;

use Gw\DHL\SDK\Base\Responses\BaseResponse;

final class ErrorResponse extends BaseResponse
{
    /**
     * @param bool $actionSucceed
     * @param \Gw\AutoCustomerGroupEu\SDK\Dto\Error[] $errors
     */
    public function __construct(
        public bool $actionSucceed,
        public array $errors
    ) {}
}
