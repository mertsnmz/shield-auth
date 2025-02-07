#!/bin/bash

# Hata durumunda scripti durdur
set -e

echo "🚀 Deployment başlatılıyor..."

# Git pull
echo "📥 Güncel kod çekiliyor..."
git pull origin main

# Composer paketlerini yükle
echo "📦 Composer paketleri yükleniyor..."
composer install --no-interaction --prefer-dist --optimize-autoloader

# Maintenance modu aktif et
echo "🔧 Bakım modu aktif ediliyor..."
php artisan down

# Environment dosyasını kopyala
echo "⚙️ Environment dosyası kontrol ediliyor..."
if [ ! -f .env ]; then
    cp .env.example .env
    php artisan key:generate
fi

# Database migration ve seed
echo "🗄️ Database güncelleniyor..."
php artisan migrate --force
php artisan db:seed --force

# Cache temizleme ve yeniden oluşturma
echo "🧹 Cache temizleniyor..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Maintenance modu kapat
echo "✅ Bakım modu kapatılıyor..."
php artisan up

echo "🎉 Deployment tamamlandı!" 