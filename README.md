# CubeCoders AMP WHMCS Module

This WHMCS module provides provisioning integration with CubeCoders AMP with WHMCS.

<h2>Installing the AMP WHMCS module</h2>

1. Upload the package content into your MAIN WHMCS DIRECTORY.  (We recommend using an FTP Client)
2. Login to WHMCS Admin Area 
3. Navigate to System Settings -> Product & Services -> Servers
4. Click on Add New Server 

- For the Module type, select AMP
- Enter the hostname for your AMP server followed by a Username and Password

- Note: You should create a seperate AMP user for WHMCS with the Super Admin Role and ensure MFA is disabled for this user.

5. Click Test Connection

- If the connection was successful you will see a green banner confirming the connection at the top

- Enter a Name for this AMP server that will be displayed in WHMCS

6. Click Save Changes
7. From the Servers overview page click on Create New Group 

- Enter a Name for your AMP Server Group
- Select your AMP Server(s) from the list and Add to Selected Servers

8. Click Save Changes

<h2>Creating the AMP Welcome & Password Reset Email Template</h2>

10. Navigate to System Settings -> System -> Email Templates
11. Click on Create New Email Template

- For Email Type, click the dropdown and select Product/Service
- Enter the name “AMP Welcome Email” for this template. 

- Note: Its important you use this exact template title name otherwise the email will not be sent once AMP has provisioned the Instance. This name will not be visible to your customers

- Enter an Email Subject that your customers will see such as “Important Account Information”
- Copy and paste the below template into the text area of the email template

		Congratulations {$client_name},
		
		Your order has now been activated and you are minuets away from starting an epic journey with your friends in {$service_product_name}
		
		
		
		Username: {$service_username}
		
		Generated Password: {$service_password}
		
		Console Login: https://{$service_server_hostname}/?instance={$service_instance_id}
		
		
		
		Lets get started!
		
		Now that you are a {$service_product_name} Server owner, you'll need to login to the above link and accept the {$service_product_name} EULA and then Start your server.
		
		Please raise a ticket on our website if you require assistance. 
		
		
		{$signature}

12. Click Save Changes
13. Next, we'll setup the AMP Password Reset email.
14. Navigate to System Settings -> System -> Email Templates
15. Click on Create New Email Template

- For Email Type, click the dropdown and select Product/Service
- Enter the name “AMP Password Reset” for this template.
- Note: Its important you use this exact template title name otherwise the email will not be sent once AMP has provisioned the Instance. This name will not be visible to your customers

- Enter an Email Subject that your customers will see such as “Password Reset Information”
- Copy and paste the below template into the text area of the email template

	  Hello {$client_name},
	
		We've reset your password to your Control Panel. Please find your new login details below:
		
		
		Username: {$service_username}
		
		Generated Password: {$service_password}
		
		Console Login: https://{$service_server_hostname}/?instance={$service_instance_id}
		
		
		
		If you did not request this password change, please reply to this email.
		
		{$signature}


<h2>Set the callback function between AMP & WHMCS</h2>

16. Login to your AMP Control Panel
17. Navigate to Configuration -> Instance Deployment
18. Set the Template deployment callback URL as follows :

		https://WHMCS_URL/index.php?ampCallback=1
		eg. https://example.com/index.php?ampCallback=1

- Ensure to change WHMCS_URL to your own domain where WHMCS is installed as this will allow AMP to tell WHMCS once the product is created and the status of the instance.


<h2>Setting up a Server Group & Product within WHMCS</h2>

19. Navigate to System Settings -> Products & Services -> roducts/Services
20. Create New Product Group ( or use existing one )

- Select Create New Product
- For the Product Type, select [Other]
- From the Product Group dropdown, select the Product Group you created earlier
- Enter a product name (For Example, Minecraft Java)
- Click on the drop down menu for Module and select AMP

21. Click on Continue 

- Click on the Module Settings tab
- Ensure that Module Name is set to AMP
- Click on the Server Group drop down menu and select the Server Group you created earlier
- You will now see two new fields appear for Provisioning Template & Post Create Action
- Select the Provisioning Template you have created within the AMP Admin selection 
- Then Select the Post Create Action such as Full Application Startup.
- Set the Required Tags field as per below
- If you are running AMP in Standalone or Hybrid mode, enter the value ‘Local’
- If you are running AMP in Controller/Target mode, enter the Tag value that you have set on a Target within the AMP panel
- Extra Provision Setting field allows you to set a static setting that will apply to all Instances created for this product. If you wish to sell customisable parameters such as RAM & CPU allocations, please see the Configurable Options section further down.
- Now select your preference for the Automatic Setup 

22. Click Save Changes

Don’t forget to set the other configurations in the other tabs for this product such as pricing.

You’re ready to go! Module is ready to use. After activation of ordered product, module will wait for callback - then Application URL will be show in client area.

<h2>Configurable Options</h2>

You can set custom configuration options within WHMCS for settings such as RAM Allocation & CPU Allocation. 

1. Navigate to System Settings -> Products & Services -> Configurable Options
2. Click on Create a New Group and give it a name such as ‘AMP Product Options’ 
3. From the Assigned Products select the products that these configurable options applies to
4. Click on Add New Configurable Option
5. Enter the configrable option that you would like to set from the list below and click Save Changes

   <h3> Example Configurable Options</h3>

		+$$ContainerCPUs|Server CPU Allocation
			2|2 CPU Cores
			4|4 CPU Cores
   		+$$ContainerMemoryMB|RAM Allowance
			1024|1 GB
			2048|2 GB
			4096|4 GB
   


<h2>FAQ’s</h2>

Q: Do I need to set a Product Welcome Email in each product?

A: No, the AMP WHMCS Module will always send out the email Template called “AMP Welcome Email”. You can however send an additional welcome email by selecting another email template in the Welcome Email drop down box within each product.

Q: Why does the WHMCS module show the instance is running straight away but then after a few seconds show as stopped?

A: Mike is aware of this issue and will hopefully be resolved in the future

Q: How can I change the URL that is displayed for the Endpoints in the welcome email or client section?

A: This URL is generated from the Hostname set in the Server created for AMP. If you use different servers, this will automatically pull the URL that the instance was provisioned to.
