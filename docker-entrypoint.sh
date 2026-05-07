#!/bin/bash
set -e

# ── 1. Fix MPM conflict at runtime ──────────────────────────────────────────
rm -f /etc/apache2/mods-enabled/mpm_*.load \
      /etc/apache2/mods-enabled/mpm_*.conf
ln -sf /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load
ln -sf /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf

# ── 2. Bind to Railway's dynamic PORT ───────────────────────────────────────
PORT="${PORT:-3306}"
echo "Configuring Apache to listen on port ${PORT}..."

# Rewrite ports.conf completely — don't rely on sed finding the right string
cat > /etc/apache2/ports.conf << PORTS
Listen ${PORT}
PORTS

# Rewrite the default vhost completely — don't rely on sed
cat > /etc/apache2/sites-enabled/000-default.conf << VHOST
<VirtualHost *:${PORT}>
    DocumentRoot /var/www/html
    <Directory /var/www/html>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    ErrorLog \${APACHE_LOG_DIR}/error.log
    CustomLog \${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
VHOST

echo "Starting Apache on port ${PORT}..."
exec apache2-foreground
