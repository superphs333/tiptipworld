# 시스템 구조, 요청 흐름, 네트워크, 데이터 구조

[README로 돌아가기](../README.md)

## 구조 이미지

![TipTipWorld Service Flow](assets/service-flow.svg)

## 컨테이너 구조

TipTipWorld는 Docker Compose 기준으로 다음 계층으로 실행됩니다.

| 계층 | 구성 | 역할 |
| --- | --- | --- |
| Web | `web` Nginx 컨테이너 | HTTP 요청 수신, 정적 파일 제공, PHP 요청을 `app:9000`으로 전달 |
| App | `app` PHP-FPM 컨테이너 | Laravel 애플리케이션 실행, Composer/NPM/Artisan 명령 실행 |
| Data | `db` MariaDB 컨테이너 | 애플리케이션 영속 데이터 저장 |
| Cache | `redis` Redis 컨테이너 | Redis 기반 캐시/세션/큐 선택 시 사용 가능한 인메모리 저장소 |

## 요청 흐름

1. 브라우저가 `tiptipworld.com`으로 HTTP 요청을 보냅니다.
2. 외부 reverse proxy가 있는 환경에서는 요청이 `proxy-nw` 네트워크의 `web` 컨테이너로 전달됩니다.
3. Nginx는 `src/public`을 document root로 사용합니다.
4. 정적 파일은 Nginx가 직접 응답하고, PHP 요청은 `app:9000`의 PHP-FPM으로 전달합니다.
5. Laravel 라우터가 `src/routes/web.php`와 `src/routes/auth.php`의 정의에 따라 컨트롤러를 호출합니다.
6. 컨트롤러는 모델, 서비스, Form Request, Policy, Blade 뷰와 협력해 응답을 생성합니다.
7. 데이터는 MariaDB, Laravel storage, 필요한 경우 Redis 또는 외부 S3/R2 호환 스토리지에 저장됩니다.

## 네트워크

| 네트워크 | 연결 서비스 | 용도 |
| --- | --- | --- |
| `proxy-nw` | `web` | 외부 reverse proxy와 Nginx 연결용 external 네트워크 |
| `tiptip-internal` | `app`, `web`, `db`, `redis` | 애플리케이션 내부 통신 |

포트 노출:

| 포트 | 서비스 | 설명 |
| --- | --- | --- |
| `5174:5173` | `app` | Vite 개발 서버 접근용 |
| `127.0.0.1:3307:3306` | `db` | 호스트 로컬에서 MariaDB 접속용 |

Nginx의 80 포트는 Compose 파일에서 직접 호스트에 publish하지 않습니다. 운영 환경에서는 `proxy-nw`에 연결된 reverse proxy 구성을 함께 확인해야 합니다.

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

## 주요 기능 흐름

| 영역 | 주요 흐름 |
| --- | --- |
| 인증 | 회원가입, 로그인, 이메일 인증, 비밀번호 재설정, Google/Kakao 로그인 |
| 팁 | 목록, 검색, 상세, 작성, 수정, 삭제, 카테고리/태그별 조회 |
| 상호작용 | 좋아요, 북마크, 댓글, 댓글 좋아요, 팔로우 |
| 사용자 | 프로필 수정, 프로필 이미지 관리, 마이페이지, 알림 |
| 관리자 | 사용자, 카테고리, 태그, 팁 관리 |
| 업로드 | 프로필 이미지, Summernote 이미지, public 또는 R2/S3 호환 디스크 저장 |

## 데이터 구조

마이그레이션 기준 주요 테이블:

| 데이터 | 관련 테이블 |
| --- | --- |
| 사용자/인증 | `users`, `password_reset_tokens`, `sessions` |
| 권한 | `roles`, `role_user` |
| 팁 콘텐츠 | `tips`, `categories`, `tags`, `tip_tag`, `statuses` |
| 반응/보관 | `tip_likes`, `tip_bookmark`, `comment_likes` |
| 댓글 | `comments` |
| 팔로우 | `user_follows` |
| 알림 | `notifications` |
| Laravel 런타임 | `cache`, `jobs`, `job_batches`, `failed_jobs` |

개발 기본값은 `src/.env.example` 기준 SQLite지만, Docker Compose 스택은 MariaDB 컨테이너를 포함합니다. Docker 환경에서는 `src/.env`의 DB 연결 정보를 Compose 서비스명 `db`에 맞춰 설정합니다.
