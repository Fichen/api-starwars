FROM fichtenbaum/laravel-swapi:1.0.0

WORKDIR /var/www/html

#COPY --chown=www-data:www-data . /var/www/html/

RUN composer dump-autoload
