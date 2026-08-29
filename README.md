# TipTipWorld

TipTipWorld는 Laravel 12 기반의 팁 공유 커뮤니티 애플리케이션임. Docker Compose로 PHP-FPM, Nginx, MariaDB, Redis를 함께 실행하며, Laravel Breeze 인증, 소셜 로그인, 팁 작성/검색/댓글/좋아요/북마크/팔로우, 관리자 기능을 포함함.

## 목차

| 구분 | README 위치 | 상세 문서 |
| --- | --- | --- |
| 설치 | [설치](#설치) | [설치 문서](docs/installation.md) |
| 구조(서버) | [구조(서버)](#구조서버) | [시스템 구조](docs/architecture.md), [운영 문서](docs/operations.md) |
| 기능 | [기능](#기능) | [기능 목록](docs/features/README.md) |

## 설치

로컬 실행은 Docker Compose 기준으로 진행함.

```bash
cp .env.example .env
cp src/.env.example src/.env
docker compose build
docker compose up -d
docker compose exec -u root app composer install
docker compose exec -u root app npm install
docker compose exec -u root app php artisan key:generate
docker compose exec -u root app npm run build
docker compose exec app php artisan migrate
```

루트 `.env`에는 Docker용 `APP_UID`, `APP_GID`, DB 초기화 값을 채우고, `src/.env`에는 Laravel 앱 URL, DB, Redis, 스토리지, 소셜 로그인 설정을 채움. 실제 secret 값은 커밋하지 않음.

## 구조(서버)

![TipTipWorld Service Flow](docs/assets/service-flow.svg)

| 서비스 | 역할 |
| --- | --- |
| `web` | Nginx로 HTTP 요청 처리, 정적 파일 제공, PHP 요청을 `app`으로 전달 |
| `app` | Laravel PHP-FPM 실행, Composer/NPM/Artisan 명령 실행 |
| `db` | MariaDB 데이터 저장 |
| `redis` | 캐시, 세션, 큐에서 사용할 수 있는 Redis 저장소 |

Laravel 애플리케이션 코드는 `src/`에 있고, 주요 코드는 `src/app`, Blade 뷰는 `src/resources/views`, 라우트는 `src/routes`, 마이그레이션은 `src/database`에 둠.

## 기능

| 기능 | 주요 내용 |
| --- | --- |
| 인증 | 회원가입, 로그인, 비밀번호 재설정, Google/Kakao 로그인 |
| 팁 | 팁 목록, 상세, 작성, 수정, 삭제, 태그/카테고리 연결 |
| 검색 | 키워드 검색, 카테고리/태그별 조회, 사용자 피드 |
| 댓글/반응 | 댓글, 답글, 좋아요, 북마크, 댓글 좋아요 |
| 팔로우 | 사용자 팔로우/언팔로우, 팔로워/팔로잉 목록 |
| 마이페이지/알림 | 프로필, 내 팁, 보관함, 댓글/좋아요/팔로우 알림 |
| 관리자 | 사용자, 카테고리, 태그, 팁 관리 |
| 이미지/미디어 | 프로필 이미지, 에디터 이미지, 썸네일, R2/S3 저장 |
