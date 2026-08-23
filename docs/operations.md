# 운영, 관리 UI 접속, 상태 확인

[README로 돌아가기](../README.md)

## 컨테이너 운영

저장소 루트에서 실행합니다.

```bash
docker compose up -d
docker compose ps
docker compose logs -f
```

특정 서비스 로그만 확인할 수 있습니다.

```bash
docker compose logs -f web
docker compose logs -f app
docker compose logs -f db
docker compose logs -f redis
```

컨테이너를 중지합니다.

```bash
docker compose down
```

MariaDB 데이터 볼륨까지 삭제하면 데이터가 사라집니다. 운영 또는 유지해야 하는 개발 데이터가 있으면 `-v` 옵션을 사용하지 않습니다.

## 접속 경로

| 화면 | 경로 | 조건 |
| --- | --- | --- |
| 홈 | `/` | 공개 |
| 팁 목록 | `/tips/` | 공개 |
| 팁 검색 | `/tips/search` | 공개 |
| 로그인 | `/login` | guest |
| 회원가입 | `/register` | guest |
| 프로필 | `/profile` | 인증 필요 |
| 마이페이지 | `/mypage/{tab?}` | 인증 필요 |
| 관리자 | `/admin/{tab?}` | `auth`, `admin` 미들웨어 필요 |

Nginx 설정의 로컬 도메인은 `tiptipworld.com`입니다. reverse proxy를 사용하지 않는 로컬 구성에서는 Nginx 80 포트 publish 설정이 별도로 필요할 수 있습니다.

## Laravel 상태 확인

```bash
docker compose exec app php artisan about
docker compose exec app php artisan route:list
docker compose exec app php artisan migrate:status
```

애플리케이션 로그는 다음 위치를 확인합니다.

```bash
docker compose exec app php artisan pail
```

또는 파일 로그를 직접 확인합니다.

```bash
docker compose exec app tail -f storage/logs/laravel.log
```

## 캐시와 설정

설정 변경 후 캐시를 비웁니다.

```bash
docker compose exec app php artisan optimize:clear
```

운영 배포에서 설정 캐시를 사용할 경우:

```bash
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
```

## 데이터베이스 운영

마이그레이션:

```bash
docker compose exec app php artisan migrate
```

마이그레이션 상태:

```bash
docker compose exec app php artisan migrate:status
```

시더:

```bash
docker compose exec app php artisan db:seed
```

호스트에서 MariaDB에 접속할 때는 `127.0.0.1:3307`을 사용합니다.

## 큐

`src/.env.example`의 기본 큐 연결은 `database`입니다. 개발 중 큐 처리가 필요하면 다음 명령을 실행합니다.

```bash
docker compose exec app php artisan queue:listen --tries=1 --timeout=0
```

운영에서 long-running worker를 사용할 경우 배포 후 worker 재시작 절차를 별도로 둡니다.

## 빌드와 검증

PHP 테스트:

```bash
docker compose exec app composer test
```

프론트엔드 빌드:

```bash
docker compose exec app npm run build
```

PHP 포맷:

```bash
docker compose exec app vendor/bin/pint
```
