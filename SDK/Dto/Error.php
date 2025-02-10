<?php
namespace Gw\AutoCustomerGroupEu\SDK\Dto;

class Error
{
    /**
     * @param string|null $error
     * @param string|null $message
     */
    public function __construct(
        public ?string $error,
        public ?string $message
    ) {}
}
