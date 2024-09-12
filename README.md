<h1>AutoCustomerGroup - European Union Addon</h1>
<p>Magento 2 Module - Module to add European Union functionality to gwharton/module-autocustomergroup</p>

<h2>European Union VAT OSS/IOSS Scheme</h2>
<p>This Scheme applies to shipments being sent from anywhere in the world to the EU. Note special procedures apply for shipments from Northern Ireland (NI, Part of UK) to the EU.</p>
<p>As of 1st July 2021, sellers may register for the EU OSS/IOSS scheme in an EU country of their choice, and collect the appropriate EU VAT at the point of sale and remit to the EU.</p>
<p>The module is capable of automatically assigning customers to the following categories.</p>
<ul>
    <li><b>Domestic</b> - For shipments within a single EU country, normal EU VAT rules apply.</li>
    <li><b>Intra-EU B2B</b> - For shipments between different countries within the EU, or from NI to the EU and the buyer presents a validated EU VAT number, then the sale can be zero rated for EU VAT. Zero Rate notice to be included on Invoice.</li>
    <li><b>Intra-EU B2C</b> - For shipments between different countries within the EU, or from NI to the EU and the buyer does NOT present a validated EU VAT number, then Destination Country VAT should be charged.</li>
    <li><b>Import B2B</b> - For shipments from outside of the EU to within the EU and the buyer presents a validated EU VAT number, then VAT should not be charged. Reverse Charge notice to be included on Invoice.</li>
    <li><b>Import Taxed</b> - For shipments from outside of the EU to the EU and the total goods value is equal to or below 150 EUR, then Destination Country VAT should be charged.</li>
    <li><b>Import Untaxed</b> - For shipments from outside of the EU to the EU and the total goods value is above 150 EUR, then VAT should NOT be charged and instead will be collected at the EU border along with any duties due.</li>
</ul>
<p>You need to create the appropriate tax rules and customer groups, and assign these customer groups to the above categories within the module configuration. Please ensure you fully understand the tax rules of the country you are shipping to. The above should only be taken as a guide.</p>

<h2>Government Information</h2>
<p>Scheme information can be found <a href="https://ec.europa.eu/taxation_customs/customs-procedures/customs-formalities-low-value-consignments_en" target="_blank">on the EU website here</a>.</p>

<h2>Order Value</h2>
<p>For the EU OSS/IOSS VAT Scheme, the following applies (This can be confirmed
    <a href="https://taxation-customs.ec.europa.eu/document/download/7bfb45b8-1f40-48b5-88e0-07960bf7ff9e_en?filename=Customs%20Guidance%20doc%20on%20LVC-Clean-20220915.pdf"
    target="_blank">here</a> in Section 1.3.1) :</p>
<ul>
    <li>Order value (for the purpose of thresholding) is the sum of the sale price of all items sold (including any discounts)</li>
    <li>When determining whether VAT should be charged (VAT Threshold) Shipping or Insurance Costs are not included in the value of the goods.</li>
    <li>When determining the amount of VAT to charge the Goods value does include Shipping and Insurance Costs.</li>
</ul>
<p>More information on the scheme can be found on the <a href="https://ec.europa.eu/taxation_customs/customs-procedures/customs-formalities-low-value-consignments_en" target="_blank">European Commission Website</a></p>

<h2>Pseudocode for group allocation</h2>
<p>Groups are allocated by evaluating the following rules in this order (If a rule matches, no further rules are evaluated).</p>
<ul>
<li>IF MerchantCountry IN EU AND CustomerCountry IN EU AND MerchantCountry EQUALS CustomerCountry THEN Group IS Domestic.</li>
<li>IF (MerchantCountry IN EU OR MerchantCountry IS NI) AND CustomerCountry IN EU AND MerchantCountry NOT EQUALS CustomerCountry AND TaxIdentifier IS VALID THEN Group IS IntraEUB2B.</li>
<li>IF (MerchantCountry IN EU OR MerchantCountry IS NI) AND CustomerCountry IN EU AND MerchantCountry NOT EQUALS CustomerCountry AND TaxIdentifier IS NOT VALID THEN Group IS IntraEUB2C.</li>
<li>IF MerchantCountry IS NOT IN EU AND CustomerCountry IN EU AND TaxIdentifier IS VALID THEN Group IS ImportB2B.</li>
<li>IF MerchantCountry IS NOT IN EU AND CustomerCountry IN EU AND OrderValue IS LESS THAN OR EQUAL TO Threshold THEN Group IS ImportTaxed.</li>
<li>IF MerchantCountry IS NOT IN EU AND CustomerCountry IN EU AND OrderValue IS MORE THAN Threshold THEN Group IS ImportUntaxed.</li>
<li>ELSE NO GROUP CHANGE</li>
</ul>

<h2>VAT Number Verification</h2>
<ul>
<li><b>Offline Validation</b> - A simple format validation is performed.</li>
<li><b>Online Validation</b> - In addition to the offline checks above, an online validation check is performed with the EU VIES service.</li>
</ul>
<p>When the module submits requests to the VIES service for VAT number validation, it can do so in two ways.</p>
<ul>
<li>The first method is anonymous, and a basic response to the validation request is given. If you leave these two fields blank, the module will use this method.</li>
<li>The second method provides more details and includes a "proof of validation" identifier which the module will store with the VAT validation details in the Magento database after verification. You can use this as proof that you checked the status. The only issue is that you have to provide a VAT registration country and VAT registration number of a business registered for VAT in the EU. Providing your IOSS details will not work. It is up to you, if you want to enable this feature, to locate suitable details to enter here. The EU will log the request with those company details, and no doubt, the IP address of your server.</li>
</ul>

<h2>Configuration Options</h2>
<ul>
<li><b>Enabled</b> - Enable/Disable this Tax Scheme.</li>
<li><b>Tax Identifier Field - Customer Prompt</b> - Displayed under the Tax Identifier field at checkout when a shipping country supported by this module is selected. Use this to include information to the user about why to include their Tax Identifier.</li>
<li><b>Validate Online</b> - Whether to validate VAT numbers with the EU VIES Service, or just perform simple format validation.</li>
<li><b>VIES Registration Country</b> - Optional. Must be valid EU country, if completed, it will be passed to the VIES with the validation request and a unique verification code will be returned and stored with the order as proof of validation.</li>
<li><b>VIES Registration Number</b> - Optional. Must be valid EU VAT number, if completed, it will be passed to the VIES with the validation request and a unique verification code will be returned and stored with the order as proof of validation.</li>
<li><b>VAT Registration Number</b> - The Scheme Registration Number for the Merchant. Supplementary functions in AutoCustomerGroup may use this, for example displaying on invoices etc.</li>
<li><b>Import VAT Threshold</b> - If the order value is above the VAT Threshold, no VAT should be charged.</li>
<li><b>Use Magento Exchange Rate</b> - To convert from EUR Threshold to Store Currency Threshold, should we use the Magento Exchange Rate, or our own.</li>
<li><b>Exchange Rate</b> - The exchange rate to use to convert from EUR Threshold to Store Currency Threshold.</li>
<li><b>Customer Group - Domestic</b> - Merchant Country is within the EU, Item is being shipped to the same country.</li>
<li><b>Customer Group - Intra-EU B2B</b> - Merchant Country is within the EU or NI, Item is being shipped to the EU, Merchant Country and Shipping Country are not the same, VAT Number passed validation by module.</li>
<li><b>Customer Group - Intra-EU B2C</b> - Merchant Country is within the EU or NI, Item is being shipped to the EU, Merchant Country and Shipping Country are not the same.</li>
<li><b>Customer Group - Import B2B</b> - Merchant Country is not within the EU, Item is being shipped to the EU, VAT Number passed validation by module.</li>
<li><b>Customer Group - Import Taxed</b> - Merchant Country is not within the EU, Item is being shipped to the EU, Order Value is below or equal to the Import VAT Threshold.</li>
<li><b>Customer Group - Import Untaxed</b> - Merchant Country is not within the EU, Item is being shipped to the EU, Order Value is above the Import VAT Threshold.</li>
</ul>

<h2>Integration Tests</h2>
<p>To run the integration tests, it is optional, but to support all functions, you should add the VIES Registration Country and VIES Registration Number. Please add them to config-global.php.</p>
<p>Please note that the EU VIES Service does not have a sandbox for testing, so live details should be used.</p>
<ul>
<li>autocustomergroup/euvat/viesregistrationcountry</li>
<li>autocustomergroup/euvat/viesregistrationnumber</li>
</ul>
