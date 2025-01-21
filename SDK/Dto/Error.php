<?php
namespace Gw\AutoCustomerGroupEu\SDK\Dto;

final class Error
{
    /**
     * @param string $error
     * @param string $message
     */
    public function __construct(
        public string $error,
        public string $message
    ) {}
}
