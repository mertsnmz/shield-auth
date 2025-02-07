cd /var/www && composer install
php artisan migrate
exit
cd /var/www && php artisan key:generate
php artisan migrate
docker exec -it auth-db mysql -uroot -p46t#kf86T
exit
