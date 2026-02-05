# =============================================================================
# FLS POC Site Builder - Production Dockerfile
# =============================================================================
# Multi-stage, immutable container build
# Eliminates need for setup.sh - all dependencies baked in at build time
# =============================================================================

# -----------------------------------------------------------------------------
# Stage 1: Base image with system dependencies
# -----------------------------------------------------------------------------
FROM php:8.2-fpm-alpine AS base

# Install system packages in a single layer for cache efficiency
# These are the packages from setup.sh that must be installed at build time
RUN apk add --no-cache \
    # Core utilities
    bash \
    curl \
    git \
    openssh-client \
    openssl \
    rsync \
    jq \
    # Python for PyNaCl SSH support
    python3 \
    py3-pip \
    py3-pynacl \
    # PHP extensions dependencies
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    # Nginx for serving
    nginx \
    # Supervisor for process management
    supervisor \
    # DNS utilities for debugging
    bind-tools \
    && rm -rf /var/cache/apk/*

# Install PHP extensions required by the application
RUN docker-php-ext-install \
    mbstring \
    zip \
    intl \
    opcache \
    && docker-php-ext-enable opcache

# -----------------------------------------------------------------------------
# Stage 2: Application build
# -----------------------------------------------------------------------------
FROM base AS builder

WORKDIR /build

# Copy application files
COPY . .

# Remove development/local files that shouldn't be in production
RUN rm -rf \
    .git \
    .gitignore \
    .DS_Store \
    *.md \
    setup.sh \
    docker-compose*.yml \
    .env* \
    logs/* \
    tmp/* \
    && find . -name ".DS_Store" -delete \
    && find . -name "*.log" -delete

# -----------------------------------------------------------------------------
# Stage 3: Production image
# -----------------------------------------------------------------------------
FROM base AS production

LABEL maintainer="FrontLine Strategies <vishnu@frontlinestrategies.co>"
LABEL description="FLS POC Site Builder - Immutable Container"
LABEL version="1.0.0"

# Create non-root user for security (matching setup.sh's nobody:nogroup)
# Alpine uses 'nobody' user with UID 65534
RUN addgroup -g 1000 appgroup && \
    adduser -u 1000 -G appgroup -s /bin/bash -D appuser

# Set working directory
WORKDIR /app

# Create required directories with proper permissions
# These directories were created by setup.sh
RUN mkdir -p \
    /app/config \
    /app/logs \
    /app/logs/api \
    /app/logs/deployment \
    /app/tmp \
    /app/uploads/images \
    /app/uploads/images/slides \
    /app/webhook/tasks \
    /app/.ssh \
    /var/log/nginx \
    /var/log/php-fpm \
    /var/run/php-fpm \
    /var/log/supervisor \
    /run/nginx \
    && chmod 700 /app/.ssh \
    && chmod 755 /app/config /app/logs /app/tmp /app/webhook \
    && chmod 777 /app/logs/api /app/logs/deployment /app/tmp

# Copy application from builder stage
COPY --from=builder /build /app

# Set ownership for application directories
# Using appuser for most files, but PHP-FPM runs as www-data
RUN chown -R appuser:appgroup /app \
    && chown -R www-data:www-data /app/logs /app/tmp /app/uploads \
    && chmod -R 755 /app/scripts \
    && chmod +x /app/scripts/*.sh 2>/dev/null || true

# Copy configuration files
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/entrypoint.sh /entrypoint.sh

# Make entrypoint executable
RUN chmod +x /entrypoint.sh

# Configure SSH client (not server - this is for outbound SSH to Kinsta)
RUN mkdir -p /root/.ssh \
    && chmod 700 /root/.ssh \
    && echo "Host *" > /root/.ssh/config \
    && echo "    StrictHostKeyChecking no" >> /root/.ssh/config \
    && echo "    UserKnownHostsFile=/dev/null" >> /root/.ssh/config \
    && chmod 600 /root/.ssh/config

# Also configure SSH for appuser (scripts may run as this user)
RUN mkdir -p /home/appuser/.ssh \
    && chmod 700 /home/appuser/.ssh \
    && echo "Host *" > /home/appuser/.ssh/config \
    && echo "    StrictHostKeyChecking no" >> /home/appuser/.ssh/config \
    && echo "    UserKnownHostsFile=/dev/null" >> /home/appuser/.ssh/config \
    && chmod 600 /home/appuser/.ssh/config \
    && chown -R appuser:appgroup /home/appuser/.ssh

# Configure PHP OPcache for production
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.interned_strings_buffer=8" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini

# Health check
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD curl -f http://localhost:8080/health || exit 1

# Expose port (nginx listens on 8080)
EXPOSE 8080

# Environment variables with defaults
ENV APP_ENV=production \
    PHP_MEMORY_LIMIT=256M \
    PHP_MAX_EXECUTION_TIME=300 \
    PHP_UPLOAD_MAX_FILESIZE=64M \
    PHP_POST_MAX_SIZE=64M \
    TZ=UTC

# Use supervisor to run nginx + php-fpm
ENTRYPOINT ["/entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
