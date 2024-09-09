<h1>AutoCustomerGroup - European Union Addon</h1>
<p>Magento 2 Module - Module to add European Union functionality to gwharton/module-autocustomergroup</p>
<h2>European Union VAT OSS/IOSS Scheme</h2>
<h3>Configuration Options</h3>
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
<h2>VIES Registration Country and VIES Registration Number</h2>
<p>When the module submits requests to the VIES service for VAT number validation, it can do so in two ways.</p>
<ul>
<li>The first method is anonymous, and a basic response to the validation request is given. If you leave these two fields blank, the module will use this method.</li>
<li>The second method provides more details and includes a "proof of validation" identifier which the module will store with the VAT validation details in the Magento database after verification. You can use this as proof that you checked the status. The only issue is that you have to provide a VAT registration country and VAT registration number of a business registered for VAT in the EU. Providing your IOSS details will not work. It is up to you, if you want to enable this feature, to locate suitable details to enter here. The EU will log the request with those company details, and no doubt, the IP address of your server.</li>
</ul>
<h2>Integration Tests</h2>
<p>To run the integration tests, it is optional, but to support all functions, you should add the VIES Registration Country and VIES Registration Number. Please add them to config-global.php.</p>
<p>Please note that the EU VIES Service does not have a sandbox for testing, so live details should be used.</p>
<ul>
<li>autocustomergroup/euvat/viesregistrationcountry</li>
<li>autocustomergroup/euvat/viesregistrationnumber</li>
</ul>
