#!/bin/bash

# A simple deployment script for the MaintenancePro application.
#
# Usage: ./deploy.sh /path/to/maintenance-pro.tar.gz /var/www/maintenance-pro
#
# Arguments:
#   $1: The path to the deployment archive (maintenance-pro.tar.gz).
#   $2: The target deployment directory.

set -e # Exit immediately if a command exits with a non-zero status.

ARCHIVE_PATH=$1
DEPLOY_PATH=$2
WEB_USER="www-data" # The user your web server runs as (e.g., www-data, apache)

# --- Validation ---
if [ -z "$ARCHIVE_PATH" ] || [ -z "$DEPLOY_PATH" ]; then
    echo "Usage: $0 <path_to_archive> <deployment_path>"
    exit 1
fi

if [ ! -f "$ARCHIVE_PATH" ]; then
    echo "Error: Archive not found at '$ARCHIVE_PATH'"
    exit 1
fi

echo "🚀 Starting deployment..."

# --- Deployment Steps ---

# 1. Create the deployment directory
echo "Creating deployment directory at '$DEPLOY_PATH'..."
mkdir -p "$DEPLOY_PATH"

# 2. Extract the application archive
echo "Extracting archive to '$DEPLOY_PATH'..."
tar -xzf "$ARCHIVE_PATH" -C "$DEPLOY_PATH"

# 3. Set up file permissions
# The web server needs to be able to write to the cache and logs directories.
echo "Setting up file permissions..."
mkdir -p "$DEPLOY_PATH/var/cache" "$DEPLOY_PATH/var/logs" "$DEPLOY_PATH/var/storage"
sudo chown -R "$WEB_USER":"$WEB_USER" "$DEPLOY_PATH/var"
sudo chmod -R 775 "$DEPLOY_PATH/var"

# 4. Final instructions
echo "✅ Deployment successful!"
echo ""
echo "Next steps:"
echo "1. Copy your production 'config.json' to '$DEPLOY_PATH/config/config.json'."
echo "2. Configure your web server (e.g., Nginx, Apache) to use '$DEPLOY_PATH/public' as the document root."
echo "3. Ensure your web server is configured to handle PHP files (e.g., via PHP-FPM)."
echo ""
echo "Example Nginx configuration:"
echo "
server {
    listen 80;
    server_name your-domain.com;
    root $DEPLOY_PATH/public;

    index index.php;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock; # Adjust to your PHP-FPM version
    }

    location ~ /\.ht {
        deny all;
    }
}
"