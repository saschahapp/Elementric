FROM php:7.3-fpm

COPY . /var/www/html/NewsletterUpdate

WORKDIR  /var/www/html/NewsletterUpdate/

RUN docker-php-ext-install bcmath 
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/bin/
RUN install-php-extensions ldap



