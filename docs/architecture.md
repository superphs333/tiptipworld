# 시스템 구조, 요청 흐름, 네트워크

[README로 돌아가기](../README.md)

## 구조 이미지

![TipTipWorld Service Flow](assets/service-flow.svg)

## 컨테이너 구조

TipTipWorld는 Docker Compose 기준으로 다음 계층으로 실행됨.

| 계층 | 구성 | 역할 |
| --- | --- | --- |
| Web | Nginx 컨테이너 | HTTP 요청 수신, 정적 파일 제공, PHP 요청을 PHP-FPM으로 전달 |
| App | `app` PHP-FPM 컨테이너 | Laravel 애플리케이션 실행, Composer/NPM/Artisan 명령 실행 |
| Data | `db` MariaDB 컨테이너 | 애플리케이션 영속 데이터 저장 |
| Cache | `redis` Redis 컨테이너 | Redis 기반 캐시/세션/큐 선택 시 사용 가능한 인메모리 저장소 |

## 요청 흐름

1. 브라우저가 설정된 애플리케이션 도메인으로 HTTP 요청을 보냄.
2. 외부 reverse proxy가 있는 환경에서는 요청이 Nginx 컨테이너로 전달됨.
3. Nginx는 `src/public`을 document root로 사용함.
4. 정적 파일은 Nginx가 직접 응답하고, PHP 요청은 PHP-FPM으로 전달함.
5. Laravel 라우터가 `src/routes/web.php`와 `src/routes/auth.php`의 정의에 따라 컨트롤러를 호출함.
6. 컨트롤러는 모델, 서비스, Form Request, Policy, Blade 뷰와 협력해 응답을 생성함.
7. 데이터는 MariaDB, Laravel storage, 필요한 경우 Redis 또는 외부 S3/R2 호환 스토리지에 저장됨.

## 네트워크

| 네트워크 | 연결 서비스 | 용도 |
| --- | --- | --- |
| 외부 프록시 네트워크 | Web | 외부 reverse proxy와 Nginx 연결 |
| 내부 애플리케이션 네트워크 | App, Web, Data, Cache | 애플리케이션 내부 통신 |

포트 노출:

| 포트 | 서비스 | 설명 |
| --- | --- | --- |
| 개발 서버 포트 | App | Vite 개발 서버 접근용 |
| 로컬 DB 포트 | Data | 호스트 로컬에서 MariaDB 접속용 |

Nginx의 80 포트는 Compose 파일에서 직접 호스트에 publish하지 않음. 운영 환경에서는 reverse proxy 구성을 함께 확인해야 함.

## Laravel 애플리케이션 구조

| 경로 | 역할 |
| --- | --- |
| `src/app/Http/Controllers` | 화면/액션 컨트롤러 |
| `src/app/Http/Requests` | 요청 검증 |
| `src/app/Models` | Eloquent 모델 |
| `src/app/Services` | 팁, 홈 화면, 팔로우, 알림, 파일 저장, 소셜 계정 해제 등 재사용 로직 |
| `src/resources/views` | Blade 화면과 컴포넌트 |
| `src/resources/css`, `src/resources/js` | Vite로 빌드되는 프론트엔드 자산 |
| `src/routes/web.php` | 공개 화면, 인증 사용자 기능, 관리자 기능 라우트 |
| `src/routes/auth.php` | Breeze 인증, Google/Kakao 소셜 로그인 라우트 |
| `src/database/migrations` | 데이터베이스 스키마 |
