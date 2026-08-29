# 서버 구성

[README로 돌아가기](../README.md)

## 구조 이미지

![TipTipWorld Service Flow](assets/service-flow.svg)

## Docker Compose 구성

TipTipWorld는 Docker Compose 기준으로 Web, App, Data, Cache 계층을 함께 실행함.

| 서비스 | 구성 | 역할 |
| --- | --- | --- |
| `web` | `nginx:1.24-alpine` | HTTP 요청 처리, `src/public` 정적 파일 제공, PHP 요청을 `app`으로 전달 |
| `app` | `docker/php/Dockerfile` | Laravel PHP-FPM 실행, Composer/NPM/Artisan 명령 실행 |
| `db` | `mariadb:10.11` | MariaDB 데이터 저장 |
| `redis` | `redis:7.2-alpine` | Redis 기반 캐시, 세션, 큐 백엔드로 사용 가능 |

## 요청 흐름

1. 브라우저가 설정된 애플리케이션 도메인으로 HTTP 요청을 보냄.
2. 외부 reverse proxy가 있는 환경에서는 요청이 Nginx 컨테이너로 전달됨.
3. Nginx는 `src/public`을 document root로 사용함.
4. 정적 파일은 Nginx가 직접 응답하고, PHP 요청은 PHP-FPM으로 전달함.
5. Laravel 애플리케이션이 응답을 생성하고 PHP-FPM이 Nginx로 결과를 반환함.
6. Nginx가 브라우저에 응답을 반환함.

## 네트워크와 볼륨

| 네트워크 | 연결 서비스 | 용도 |
| --- | --- | --- |
| 외부 프록시 네트워크 | Web | 외부 reverse proxy와 Nginx 연결 |
| 내부 애플리케이션 네트워크 | App, Web, Data, Cache | 애플리케이션 내부 통신 |

주요 마운트는 `./src`, `docker/nginx/default.conf`, `docker/php/conf.d/uploads.ini`, MariaDB 영속 볼륨임. Xdebug 설정은 `APP_ENV=local`로 빌드할 때만 이미지에 포함됨.

Nginx의 80 포트는 Compose 파일에서 직접 호스트에 publish하지 않음. 앱 개발 서버와 로컬 DB 접근용 포트만 호스트 로컬로 제한해 노출함.

## 런타임

`app` 이미지는 PHP 8.4 FPM 기반이며 `pdo_mysql`, `gd`, `zip`, `bcmath`, `redis` 확장과 Node.js 22.x, Composer를 포함함. Xdebug는 루트 `.env`의 `APP_ENV=local` 값으로 빌드할 때만 설치함.
