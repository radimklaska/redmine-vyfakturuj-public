# Vyfakturuj.cz invoice from Redmine

* Fill in your data in the invoice object here: `src/Command/RVCommand.php:144`
* `lando start`
* `l php bin/console sync redmine.domain.com redmin_api_key redmine_user_id hourly_rate vyfakturuj_mail vyfakturuj_api_key -v`


## Set up following secrets and variables in your repository:

* variables
  * REDMINEDOMAIN
    * Example: `redmine.domain.com`
  * REDMINEUSERID
    * Example: `1`
  * VYFAKTURUJLOGIN
    * Example: `user@domain.com`
* secrets
  * REDMINEAPIKEY
    * Example: `aa***aa`
  * HOURLYRATE
    * Example: `100`
  * VYFAKTURUJAPI
    * Example: `aa***aa`