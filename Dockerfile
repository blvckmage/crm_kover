FROM php:8.2-apache

# Включаем поддержку перенаправлений Apache (на всякий случай)
RUN a2enmod rewrite

# Копируем все файлы проекта в директорию сервера
COPY . /var/www/html/

# Выдаем права пользователю сервера на чтение и запись файлов
RUN chown -R www-data:www-data /var/www/html/
