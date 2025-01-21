<?php
namespace Gw\AutoCustomerGroupEu\SDK\Responses;

final class ErrorResponse
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
