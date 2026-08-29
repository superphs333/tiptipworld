# 설치 및 실행 방법

[README로 돌아가기](../README.md)

## 1. 필수 도구

- Docker와 Docker Compose
- Git
- 로컬에서 직접 앱 명령을 실행할 경우 PHP, Composer, Node.js, npm

Docker 컨테이너 안에서 앱 명령을 실행할 경우 로컬 PHP/Composer/Node.js 설치는 필수는 아님.

## 2. 저장소 구조 확인

- 루트 디렉터리: Docker Compose, Nginx/PHP Docker 설정, 루트 `.env`
- `src/`: Laravel 12 애플리케이션
- `docker/`: Nginx와 PHP 컨테이너 설정

## 3. 환경 파일 생성

루트에서 Docker용 `.env`를 준비함.

```bash
cp .env.example .env
```

루트 `.env`에 로컬 사용자 UID/GID와 데이터베이스 초기화 값을 채움.

```dotenv
APP_UID=1000
APP_GID=1000

# 데이터베이스 초기화 값은 .env.example의 항목을 기준으로 채움
```

`APP_UID`와 `APP_GID`는 다음 명령으로 확인한 값을 사용함.

```bash
id -u
id -g
```

Laravel 환경 파일을 준비함.

```bash
cp src/.env.example src/.env
```

`src/.env`에서 앱 URL, DB, Redis, 스토리지, 소셜 로그인 설정을 개발 환경에 맞게 수정함.

파일 업로드, 외부 스토리지, 소셜 로그인 사용 시 관련 환경변수는 로컬에서만 설정함. 실제 secret 값은 커밋하지 않음.

## 4. 로컬 도메인과 네트워크 확인

Nginx 설정의 `server_name`은 환경별 개발 도메인에 맞춤. 로컬 브라우저에서 이 도메인으로 접속하려면 hosts 파일에 개발 서버 주소를 등록함.

예시:

```text
127.0.0.1 your-local-domain.test
```

프록시 컨테이너 또는 외부 reverse proxy를 사용하는 환경에서는 Compose 설정에 맞는 external Docker 네트워크가 먼저 존재해야 함.

```bash
docker network create <external-proxy-network>
```

이미 존재하는 네트워크라면 위 명령은 생략함.

## 5. Docker 이미지 빌드와 컨테이너 실행

저장소 루트에서 실행함.

```bash
docker compose build --no-cache
docker compose up -d
```

일반적인 재빌드가 필요할 때는 캐시를 유지해도 됨.

```bash
docker compose build
docker compose up -d
```

## 6. PHP 의존성 설치

컨테이너를 통해 설치함.

```bash
docker compose exec -u root app composer install
```

호스트에서 권한이 꼬이면 설치 후 소유자와 쓰기 권한을 정리함.

```bash
sudo chown -R "$USER:$USER" src/vendor
chmod -R 775 src/storage src/bootstrap/cache
```

## 7. 앱 키 생성

```bash
docker compose exec -u root app php artisan key:generate
```

## 8. 프론트엔드 의존성 설치와 빌드

```bash
docker compose exec -u root app npm install
docker compose exec -u root app npm run build
```

필요하면 호스트 권한을 정리함.

```bash
sudo chown -R "$USER:$USER" src/node_modules src/public/build
```

## 9. 데이터베이스 마이그레이션

```bash
docker compose exec app php artisan migrate
```

초기 데이터가 필요한 경우 시더를 함께 실행함.

```bash
docker compose exec app php artisan db:seed
```

## 10. 개발 서버

Docker Compose의 Nginx를 통해 접속하는 경우 로컬 hosts 설정과 reverse proxy 설정을 확인한 뒤 설정한 개발 도메인으로 접속함.

Vite 개발 서버만 별도로 실행하려면 다음 명령을 사용함.

```bash
docker compose exec app npm run dev
```

Laravel, queue, logs, Vite를 함께 실행하는 로컬 개발 명령은 `src/` 기준임.

```bash
composer run dev
```

컨테이너 안에서 실행하려면 다음처럼 호출함.

```bash
docker compose exec app composer run dev
```

## 11. 설치 확인

컨테이너 상태를 확인함.

```bash
docker compose ps
```

Laravel 라우트가 로드되는지 확인함.

```bash
docker compose exec app php artisan route:list
```
