# YNAB bunq Connector Bundle

A Symfony 3.4 LTS compatible Bundle for uploading payments from the bunq Bundle to YNAB

## Realtime connection between bunq and YNAB
Uses [symfony-bunq-bundle](https://github.com/Jorijn/symfony-bunq-bundle) to subscribe to bank account mutation events and creates transactions in YNAB.

_More documentation coming soon, work in progress_

## Instructions


### Environment

I used to make this instructions an ubuntu machine from Amazon EC2.

Maybe you you need to change something to get it working.

Make sure you have SSH access to the machine you are trying to access. It will need a HTTPS web server with PHP installed (PHP-cli also). And you will need a DNS to allow Bunq to send you the notifications.

### Steps

1. Install apache + php;

   You can follow the instructions from: https://www.vultr.com/docs/how-to-install-apache-mysql-and-php-on-ubuntu-16-04

2. Enable HTTPS support:

   Create a new certificate and follow the instructions from: https://www.sslforfree.com/

3. Create the symfony project that will hold the bundle:

   ```shell
   $ composer create-project symfony/framework-standard-edition bunq-ynab
   ```

4. Require the bundle:

   ```shell
   $composer require jorijn/ynab-bunq-connector-bundle
   ```

   Maybe you will need to use *--ignore-platform-reqs* 

5. Configure the symfony installation

   Take care, maybe you will need to override some configuration and choose the best place to put it. Below the list of files that need to be updated and the key=>values.

   1. **<project>/app/config/services.yml**

      ```yaml
      parameters:
          router.request_context.host: 'www.your-url-here.com'
          router.request_context.scheme: 'https'
      ```

   2. **<project>/app/config/routing.yml**

      ```yaml
      symfony_bunq.callback_url:
          path:     /bunq/callback
          defaults: { _controller: JorijnSymfonyBunqBundle:Bunq:callback }
      ```

   3. **<project>/app/config/config.yml**

      ```yaml
      jorijn_ynab_bunq_connector:
          connections:
          -
              bunq_account_id:      **change_me**
              ynab_budget_id:       **change_me**
              ynab_account_id:      **change_me**
          api_key: **change_me**
      ```

6. Enable the symfony bundles:

   **<project>/app/AppKernel.php** :

   ```php
   <?php
   //Inside the method registerBundles() of the class AppKernel:
       $bundles[] = new \Jorijn\SymfonyBunqBundle\JorijnSymfonyBunqBundle;
       $bundles[] = new \Jorijn\YNAB\BunqConnectorBundle\JorijnYNABBunqConnectorBundle;
   ```

7. Run the **bin/console** commands to configure the bunq connector:

   This first command you will have to inform the API key (get it into the Bunq app);

   ```shell
   <project>$ php bin/console bunq:initialize
   ```

   Set the callback url for bunq

   ```shell
   <project>$ php bin/console bunq:callback-url
   ```

   You can see other availables commands by running:

   ```shell
   <project>$ php bin/console list
   ```

8. Done!
