# 서버셋팅 
```bash
cd tiptipworld
touch .env
# .env 파일, default.conf 파일 수정

docker compose build --no-cache
docker compose up -d

```

# 라라벨 셋팅
```bash
cd src
vi .env
docker compose exec -u root app composer install
sudo chown -R devl333:devl333 vendor
chmod -R 775 storage bootstrap/cache
su devl333
sudo chown -R devl333:devl333 .
chmod 664 .env
chmod -R 777 storage bootstrap/cache
docker compose exec -u root app php artisan key:generate
```
