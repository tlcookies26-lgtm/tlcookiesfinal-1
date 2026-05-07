#!/bin/bash
set -e

# Fix MPM conflict at runtime — build-time fixes can be silently undone by layer caching.
# Wipe ALL mpm symlinks and re-create only mpm_prefork. This is guaranteed to run
# every time the container starts, regardless of what happened at build time.
rm -f /etc/apache2/mods-enabled/mpm_*.load \
      /etc/apache2/mods-enabled/mpm_*.conf
ln -sf /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load
ln -sf /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf

echo "Active MPM modules:"
ls /etc/apache2/mods-enabled/mpm_*

# Railway injects a dynamic $PORT — configure Apache to use it
PORT="${PORT:-80}"

sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/:80>/:${PORT}>/" /etc/apache2/sites-enabled/000-default.conf

echo "Starting Apache on port ${PORT}..."
exec apache2-foreground
