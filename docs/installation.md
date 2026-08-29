# 최초 설치 및 실행 방법

[README로 돌아가기](../README.md)

## 1. 환경 파일 생성

루트에서 Docker용 `.env`를 준비합니다.

```bash
cp .env.example .env
```

루트 `.env`에 로컬 사용자 UID/GID와 MariaDB 초기화 키를 채웁니다.

```dotenv
APP_UID=1000
APP_GID=1000

MYSQL_ROOT_PASSWORD=
MYSQL_DATABASE=
MYSQL_USER=
MYSQL_PASSWORD=
```

`APP_UID`와 `APP_GID`는 다음 명령으로 확인한 값을 사용합니다.

```bash
id -u
id -g
```

Laravel 환경 파일을 준비합니다.

```bash
cp src/.env.example src/.env
```

`src/.env`에서 앱 URL, DB, Redis, 스토리지, 소셜 로그인 설정을 개발 환경에 맞게 수정합니다.

## 2. Docker 이미지 빌드와 컨테이너 실행

저장소 루트에서 실행합니다.

```bash
docker compose build --no-cache
docker compose up -d
```

일반적인 재빌드가 필요할 때는 캐시를 유지해도 됩니다.

```bash
docker compose build
docker compose up -d
```

## 3. PHP 의존성 설치

컨테이너를 통해 설치합니다.

```bash
docker compose exec -u root app composer install
```

호스트에서 권한이 꼬이면 설치 후 소유자와 쓰기 권한을 정리합니다.

```bash
sudo chown -R "$USER:$USER" src/vendor
chmod -R 775 src/storage src/bootstrap/cache
```

## 4. 앱 키 생성

```bash
docker compose exec -u root app php artisan key:generate
```

## 5. 프론트엔드 의존성 설치와 빌드

```bash
docker compose exec -u root app npm install
docker compose exec -u root app npm run build
```

필요하면 호스트 권한을 정리합니다.

```bash
sudo chown -R "$USER:$USER" src/node_modules src/public/build
```

## 6. 데이터베이스 마이그레이션

```bash
docker compose exec app php artisan migrate
```

초기 데이터가 필요한 경우 시더를 함께 실행합니다.

```bash
docker compose exec app php artisan db:seed
```

## 7. 개발 서버

Docker Compose의 Nginx를 통해 접속하는 경우 로컬 hosts 설정과 reverse proxy 설정을 확인한 뒤 `http://tiptipworld.com`으로 접속합니다.

Vite 개발 서버만 별도로 실행하려면 다음 명령을 사용합니다.

```bash
docker compose exec app npm run dev
```

Laravel, queue, logs, Vite를 함께 실행하는 로컬 개발 명령은 `src/` 기준입니다.

```bash
composer run dev
```

컨테이너 안에서 실행하려면 다음처럼 호출합니다.

```bash
docker compose exec app composer run dev
```

## 8. 설치 확인

컨테이너 상태를 확인합니다.

```bash
docker compose ps
```

Laravel 라우트가 로드되는지 확인합니다.

```bash
docker compose exec app php artisan route:list
```
