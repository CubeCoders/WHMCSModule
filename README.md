# CubeCoders AMP WHMCS Module

This WHMCS module provides provisioning integration with CubeCoders AMP with WHMCS.

<h2>Prerequisites</h2>
This guide assumes you have knowledge of WHMCS and the software is configured for basic use. More information can be found on https://www.whmcs.com.

WHMCS must be configured as its own subdomain (e.g. whmcs.example.com).

WHMCS CANNOT be in Maintenance Mode or you will fail to receive callbacks.

Ensure you have configured your ADS instances by following the [Configuring AMP for Enterprise or Network Usage](https://discourse.cubecoders.com/t/configuring-amp-for-enterprise-or-network-usage/1830) guide.

<h2>Installing the AMP WHMCS module</h2>

1. Download the [Zip file](https://github.com/CubeCoders/WHMCSModule/archive/refs/heads/main.zip) from this GitHub
2. Upload the package content into your main WHMCS directory.  (We recommend using an FTP Client)

![image](https://github.com/CubeCoders/WHMCSModule/assets/96364530/d782288e-68bc-4024-9637-9a51ab3a0e0c)

4. Login to WHMCS Admin Area 
5. Navigate to **System Settings -> Product & Services -> Servers**
6. Click on **Add New Server** and enter the following:

- Name - Any name to distinguish the server in WHMCS
- Hostname - The URI for your server excluding `https://`
- Module - **AMP**
- Username and Password - An AMP user made specifically for use with WHMCS that has the Super Admin role and MFA disabled
- Secure - Check the box for "Check to use SSL Mode for Connections"

![image](https://github.com/CubeCoders/WHMCSModule/assets/96364530/355daf3d-cd5d-4ef0-93ae-99d805595d39)
![image](https://github.com/CubeCoders/WHMCSModule/assets/96364530/9f591032-8c97-4990-a1eb-75bc32cf6da5)


6. Click **Test Connection**

- If the connection was successful you will see a green banner that says `Connection successful. Some values have been auto-filled.`

7. Click **Save Changes**
8. From the Servers overview page click on **Create New Group** 

- Enter a Name for your AMP Server Group
- Select your AMP Server(s) from the list and Add to Selected Servers

![image](https://github.com/CubeCoders/WHMCSModule/assets/96364530/a6190160-bc24-4cf4-ba5b-ab82f9e52217)

9. Click **Save Changes**

<h2>Creating the AMP Welcome & Password Reset Email Template</h2>

10. Navigate to **System Settings -> System -> Email Templates**
11. Click on **Create New Email Template**

- For Email Type, click the dropdown and select **Product/Service**
- Enter the name “AMP Welcome Email” for this template. 

 ⚠️ Its important you use this exact template title name otherwise the email will not be sent once AMP has provisioned the Instance. This name will not be visible to your customers

- Enter an Email Subject that your customers will see such as “Important Account Information”
- Copy and paste the below template into the text area of the email template

		Congratulations {$client_name},
		
		Your order has now been activated and you are minutes away from starting an epic journey with your friends in {$service_product_name}
		
		
		
		Username: {$service_username}
		
		Generated Password: {$service_password}
		
		Console Login: https://{$service_server_hostname}/?instance={$service_instance_id}
		
		
		
		Lets get started!
		
		Now that you are a {$service_product_name} Server owner, you'll need to login to the above link and accept the {$service_product_name} EULA and then Start your server.
		
		Please raise a ticket on our website if you require assistance. 
		
		
		{$signature}

12. Click **Save Changes**
13. Next, we'll setup the AMP Password Reset email.
14. Navigate to **System Settings -> System -> Email Templates**
15. Click on **Create New Email Template**

- For Email Type, click the dropdown and select **Product/Service**
- Enter the name “AMP Password Reset” for this template.

⚠️ Its important you use this exact template title name otherwise the email will not be sent once AMP has provisioned the Instance. This name will not be visible to your customers

- Enter an Email Subject that your customers will see such as “Password Reset Information”
- Copy and paste the below template into the text area of the email template

	  Hello {$client_name},
	
		We've reset your password to your Control Panel. Please find your new login details below:
		
		
		Username: {$service_username}
		
		Generated Password: {$service_password}
		
		Console Login: https://{$service_server_hostname}/?instance={$service_instance_id}
		
		
		
		If you did not request this password change, please reply to this email.
		
		{$signature}

16: Click **Save Changes**

<h2>Set the callback function between AMP & WHMCS</h2>

17. Login to your AMP Control Panel
18. Navigate to **Configuration -> Instance Deployment**
19. Set the Template deployment callback URL as follows :

		https://WHMCS_URL/index.php?ampCallback=1
		eg. https://example.com/index.php?ampCallback=1

⚠️ Ensure to change WHMCS_URL to your own domain where WHMCS is installed as this will allow AMP to tell WHMCS once the product is created and the status of the instance.

![image](https://github.com/CubeCoders/WHMCSModule/assets/96364530/d9a63007-3fa5-419f-952c-0108e268f64b)

<h2>Setting up a Server Group & Product within WHMCS</h2>

20. Navigate to **System Settings -> Products & Services -> Products/Services**
21. Create New Product Group ( or use existing one )

- Select **Create New Product**
- For the Product Type, select **[Other]**
- From the Product Group dropdown, select the Product Group you created earlier
- Enter a product name (For Example, Minecraft Java)
- Click on the drop down menu for Module and select **AMP**

22. Click on **Continue** 

- Click on the **Module Settings** tab
- Ensure that Module Name is set to **AMP**
- Click on the Server Group drop down menu and select the Server Group you created earlier

![image](https://github.com/user-attachments/assets/31af6d75-6db5-457c-81d7-3538a5e6d1b9)


- You will now see new fields appear that are specific to this product
- The **Provisioning Template** allows you to select your deployment template within AMP for specific server you want to deploy
- Then select the **Post Create Action** from the below list depending on your desired outcome. The instance is always started. Updates and/or starts may not complete if additional setup is required on the customers behalf such as EULA acknowledgement or Steam sign in.

		Do Nothing = Doesn't start the application
		Update Once = Updates but doesn't start the application automatically
		Update Always = Performs an application update every instance start
		Update and Start Once = Updates the application and starts the application once
		Update and Start Always = Updates the application and starts the application every instance start
		Start Always = Updates and starts the application once, then starts the application every future instance start
  
- Set the **Required Tags** field as per below
- If you are running AMP in **Standalone** or Hybrid mode, enter the value ‘Local’
- If you are running AMP in **Controller/Target** mode, enter the Tag value that you have set on a Target within the AMP panel
- Extra Provision Setting field allows you to set a static setting that will apply to all Instances created for this product. If you wish to sell customizable parameters such as RAM & CPU allocations, please see the Configurable Options section further down.
- The **Mode** selection allows you to specify how the Endpoints are displayed in the Client section of WHMCS. For example:
  
		Standalone URL will be displayed as https://yourdomain.com:25565
		Target/Node IP Address will be displayed as 0.0.0.0:25565
  
- Now select your preference for the Automatic Setup 

23. Click **Save Changes**

⚠️ Don’t forget to set the other configurations in the other tabs for this product such as pricing.

You’re ready to go! Module is ready to use. After activation of ordered product, module will wait for callback - then Application URL will be show in client area.

<h2>Configurable Options & Custom Fields</h2>

You can set custom configuration options within WHMCS for settings such as RAM Allocation & CPU Allocation. 

1. Navigate to **System Settings -> Products & Services -> Configurable Options**
2. Click on **Create a New Group** and give it a name such as ‘AMP Product Options’ 
3. From the Assigned Products select the products that these configurable options applies to
4. Click on **Add New Configurable Option**
5. Enter the configurable option that you would like to set from the list below and click **Save Changes**

   <h3> Example Configurable Options</h3>

		+$$ContainerCPUs|Server CPU Allocation
			2|2 CPU Cores
			4|4 CPU Cores
   		+$$ContainerMemoryMB|RAM Allowance
			1024|1 GB
			2048|2 GB
			4096|4 GB
  If you want to add additional Required Tags that are selectable by the client, you can prefix the settings with `@` like below:
  
		@RequiredTags|Datacenter
			US|United States
			EU|Europe

![image](https://github.com/user-attachments/assets/6da3fbd2-9d65-40a7-a6d0-ada9325d38d6)
![image](https://github.com/user-attachments/assets/e86b84b7-764b-446c-b2af-3e13ab5f91c2)

6. You can configure Custom Fields in a similar way by navigating back to **System Settings -> Products & Services -> Products/Services**. These can be useful for allowing a user to set values for settings on server creation.

![image](https://github.com/user-attachments/assets/9de9379e-3c1d-41af-b2be-849d8f216806)
  
7. Further values can be set using a template as described in the following article or can be set in WHMCS directly by adding a `+` to the beginning of the key.
[Configuring AMP for Enterprise or Network Usage](https://discourse.cubecoders.com/t/configuring-amp-for-enterprise-or-network-usage/1830)

<h2>Troubleshooting</h2>

You can view a large portion of the communication between AMP and WHMCS by enabling debug logging in both.
1. In WHMCS, go to **System Logs -> Module Log** and enable Module Logging.
2. In AMP, search for "Debug" in the top right to change the logging level.
3. Repeat the action you were trying and look for output from both sides.
4. If you cannot figure out the issue on your own, you can share the logs through the support channels.

⚠️ The logs contain sensitive information, so be sure to remove any details you do not want public before sharing for support!

![image](https://github.com/CubeCoders/WHMCSModule/assets/96364530/8340d40b-0a4b-43eb-9e85-4c6e0aa63718)

<h2>FAQ’s</h2>

Q: Do I need to set a Product Welcome Email in each product?

A: No, the AMP WHMCS Module will always send out the email Template called “AMP Welcome Email”. You can however send an additional welcome email by selecting another email template in the Welcome Email drop down box within each product.
