FROM php:8.2-cli
RUN docker-php-ext-install mysqli
WORKDIR /app
COPY . .
CMD ["sh", "-c", "for i in 1 2 3 4 5; do php init_db.php && break || sleep 3; done; exec php -S 0.0.0.0:$PORT"]
