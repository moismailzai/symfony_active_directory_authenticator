# Building a Symfony Active Directory Authenticator using LDAP
This is a simple example that authenticates against 
[ForumSystems](https://www.forumsys.com/tutorials/integration-how-to/ldap/online-ldap-test-server/) public Active 
Directory service. You can log in using any of the following login / password pairs:

* `einstein` / `password`
* `euclid` / `password`
* `euler` / `password`
* `galieleo` / `password`
* `gauss` / `password`
* `newton` / `password`
* `riemann` / `password`
* `tesla` / `password`

The authenticator checks the credentials against Active Directory using LDAPS, and if a bind is successful, generates
a local user and logs the user in. If a local user already exists, it is validated against Active Directory and logged 
in.

## Installation
First, update `.env` to change the database user, password, or type to match your environment. Then, install with composer, create the database, and run the migrations:
```
composer install
php bin/console doctrine:database:create --if-not-exists --no-interaction
php bin/console doctrine:migrations:migrate --no-interaction
```
Now point the web server of your choice to the `public` directory and load the corresponding domain name in your browser.
