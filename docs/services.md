# 포함된 서비스 목록과 역할

[README로 돌아가기](../README.md)

## Docker Compose 서비스

| 서비스 | 이미지/빌드 | 역할 | 포트/네트워크 | 상세 문서 |
| --- | --- | --- | --- | --- |
| `app` | `docker/php/Dockerfile` | Laravel PHP-FPM 실행, Composer/NPM/Artisan 명령 실행 | 개발 서버 포트, 내부 네트워크 | [src/README.md](../src/README.md) |
| `web` | `nginx:1.24-alpine` | HTTP 요청 처리, public 디렉터리 제공, PHP-FPM 프록시 | 외부 프록시 네트워크, 내부 네트워크 | - |
| `db` | `mariadb:10.11` | MariaDB 데이터 저장 | 로컬 DB 포트, 내부 네트워크 | - |
| `redis` | `redis:7.2-alpine` | Redis 캐시/세션/큐 백엔드로 사용 가능 | 내부 네트워크 | - |

서비스별 README 링크는 실제 파일이 존재하는 `src/README.md`에만 연결했음.

## 볼륨

| 볼륨/마운트 | 연결 서비스 | 역할 |
| --- | --- | --- |
| `./src:/var/www/html` | `app`, `web` | Laravel 애플리케이션 소스 공유 |
| `./docker/php/conf.d/xdebug.ini` | `app` | Xdebug PHP 설정 |
| `./docker/php/conf.d/uploads.ini` | `app` | PHP 업로드 제한 설정 |
| `./docker/nginx/default.conf` | `web` | Nginx 서버 설정 |
| `tiptip_db_data:/var/lib/mysql` | `db` | MariaDB 영속 데이터 |

## PHP 런타임

`app` 이미지는 `php:8.4-fpm` 기반이며 다음 런타임 요소를 포함함.

- PHP 확장: `pdo_mysql`, `gd`, `zip`, `bcmath`, `redis`, `xdebug`
- Node.js 22.x와 npm
- Composer

`src/composer.json`은 PHP `^8.2`와 Laravel `^12.0`을 요구함. 컨테이너는 이 요구사항보다 높은 PHP 8.4 런타임을 사용함.

## 프론트엔드

Vite가 `src/resources/css`와 `src/resources/js` 자산을 빌드함. 주요 프론트엔드 의존성은 Tailwind CSS, Alpine.js, Axios, Tiptap, jQuery, Summernote임.

개발 서버 포트는 Docker Compose 설정을 따름.
