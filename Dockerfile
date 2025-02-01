# Utilisation de l'image PHP officielle avec Apache
FROM php:7.4-apache

# Activation du module de réécriture d'URL d'Apache (utile pour certaines applications web)
RUN a2enmod rewrite

# Copie des fichiers de ton projet dans le dossier web d'Apache
COPY . /var/www/html/

# Définir les permissions correctes
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

# Exposition du port 80 (par défaut pour les applications web)
EXPOSE 80

# Lancement d'Apache en mode premier plan
CMD ["apache2-foreground"]
