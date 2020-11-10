# PHP Docker image for Yii 2.0 Framework runtime
# ==============================================

FROM ubuntu:latest

# Install system packages for PHP extensions recommended for Yii 2.0 Framework
ENV DEBIAN_FRONTEND=noninteractive
RUN apt-get update && \
    apt-get -y install \
        gnupg2 && \
    apt-get -y install \
            g++ \
            git \
            curl \
            imagemagick \
            libcurl3-dev \
            libicu-dev \
            libfreetype6-dev \
            libjpeg-dev \
            libonig-dev \
            libmagickwand-dev \
            libpq-dev \
            libpng-dev \
            libxml2-dev \
            libzip-dev \
            zlib1g-dev \
            default-mysql-client \
            openssh-client \
            nano redis \
            unzip \
            libcurl4-openssl-dev \
            libssl-dev php7.4 php7.4-cli php7.4-dom php7.4-curl php7.4-xml php7.4-mbstring php7.4-sqlite3 composer \
        --no-install-recommends && \
        apt-get clean && \
        rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Install PHP extensions required for Yii 2.0 Framework
copy . /app

# Install composer plugins
RUN composer global require --optimize-autoloader \
        "hirak/prestissimo" && \
    composer global dumpautoload --optimize && \
    composer clear-cache

# Install Yii framework bash autocompletion
RUN curl -L https://raw.githubusercontent.com/yiisoft/yii2/master/contrib/completion/bash/yii \
        -o /etc/bash_completion.d/yii
# Application environment
WORKDIR /app
RUN composer update
ENTRYPOINT /bin/bash /app/rundocker.sh
