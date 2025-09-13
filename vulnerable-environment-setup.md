# CVE-2024-8353 GiveWP Vulnerable Environment Setup

**PR Number**: #13157
**CVE**: CVE-2024-8353
**Template**: CVE-2024-8353.yaml

## Docker Setup for Testing

### 1. WordPress with GiveWP 3.16.1

```dockerfile
FROM wordpress:6.4-php8.1-apache

# Install GiveWP 3.16.1 (vulnerable version)
RUN curl -L "https://downloads.wordpress.org/plugin/give.3.16.1.zip" -o /tmp/give.zip && \
    unzip /tmp/give.zip -d /var/www/html/wp-content/plugins/ && \
    rm /tmp/give.zip

# Enable the plugin
RUN echo "<?php" > /var/www/html/wp-content/mu-plugins/activate-give.php && \
    echo "add_action('init', function() {" >> /var/www/html/wp-content/mu-plugins/activate-give.php && \
    echo "    activate_plugin('give/give.php');" >> /var/www/html/wp-content/mu-plugins/activate-give.php && \
    echo "});" >> /var/www/html/wp-content/mu-plugins/activate-give.php

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html/wp-content/plugins/give
```

### 2. Docker Compose

```yaml
version: '3.8'
services:
  wordpress:
    build: .
    ports:
      - "8080:80"
    environment:
      WORDPRESS_DB_HOST: db
      WORDPRESS_DB_USER: wordpress
      WORDPRESS_DB_PASSWORD: wordpress
      WORDPRESS_DB_NAME: wordpress
    depends_on:
      - db

  db:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: wordpress
      MYSQL_USER: wordpress
      MYSQL_PASSWORD: wordpress
      MYSQL_ROOT_PASSWORD: rootpassword
    volumes:
      - db_data:/var/lib/mysql

volumes:
  db_data:
```

### 3. Manual Setup Instructions

1. **Install WordPress** (any recent version)
2. **Download GiveWP 3.16.1** from: https://downloads.wordpress.org/plugin/give.3.16.1.zip
3. **Install the plugin** via WordPress admin or upload to `/wp-content/plugins/`
4. **Activate the plugin** in WordPress admin
5. **Create a donation form** using GiveWP
6. **Test the template** against the vulnerable site

### 4. Testing the Template

```bash
# Test against the vulnerable environment
nuclei -t CVE-2024-8353.yaml -u http://localhost:8080 -debug

# Expected behavior:
# - Template should detect the vulnerability
# - OAST callback should be received
# - Template should return positive result
```

### 5. Verification Steps

1. **Check GiveWP version**: Visit `/wp-content/plugins/give/readme.txt` - should show version 3.16.1
2. **Verify plugin is active**: Check WordPress admin plugins page
3. **Test donation form**: Ensure GiveWP donation forms are accessible
4. **Run template**: Execute the nuclei template against the site

### 6. Expected Results

- **Vulnerable site**: Template should detect the vulnerability and show positive results
- **Patched site**: Template should not trigger false positives
- **Non-WordPress site**: Template should not trigger false positives

## Contact Information

**Template Author**: bryanwills
**GitHub**: https://github.com/bryanwills
**Template Location**: `http/cves/2024/CVE-2024-8353.yaml`
**Branch**: `issue-13130-cve-2024-8353-givewp-php-object-injection`

## Additional Notes

- The template uses OAST (Out-of-band Application Security Testing) for reliable detection
- No authentication required - targets unauthenticated endpoints
- Template includes comprehensive error handling and validation
- All references and metadata properly included per nuclei template standards
