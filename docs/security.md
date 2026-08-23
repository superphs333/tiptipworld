# 보안 설정과 주의사항

[README로 돌아가기](../README.md)

## Secret 관리

- 루트 `.env`와 `src/.env`의 실제 값을 커밋하지 않습니다.
- 문서, 로그, 이슈, PR 설명에는 secret 값을 적지 않고 key 이름과 설정 위치만 적습니다.
- 새 필수 환경변수를 추가할 때는 예시 값만 `src/.env.example`에 반영합니다.

주요 secret 또는 민감 설정 위치:

| 영역 | 키 |
| --- | --- |
| Docker MariaDB 초기화 | `MYSQL_ROOT_PASSWORD`, `MYSQL_PASSWORD` |
| Laravel 앱 | `APP_KEY` |
| DB 연결 | `DB_PASSWORD` |
| AWS/R2 스토리지 | `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `R2_ACCESS_KEY_ID`, `R2_SECRET_ACCESS_KEY` |
| Google 로그인 | `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI` |
| Kakao 로그인 | `KAKAO_CLIENT_ID`, `KAKAO_CLIENT_SECRET`, `KAKAO_REDIRECT_URI` |

## 환경 설정

운영 환경에서는 다음 값을 개발 기본값으로 두지 않습니다.

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example
```

운영 배포 전 설정 캐시를 갱신하고, 값이 잘못 캐시된 경우 `php artisan optimize:clear`로 비운 뒤 다시 캐시합니다.

## 파일 권한

Laravel 쓰기 대상:

- `src/storage`
- `src/bootstrap/cache`

권장 기본 권한:

```bash
chmod -R 775 src/storage src/bootstrap/cache
```

문제가 생겨도 전체 애플리케이션 디렉터리에 무조건 `777`을 적용하지 않습니다. 쓰기가 필요한 경로만 제한적으로 조정합니다.

## 업로드와 스토리지

파일 업로드는 PHP와 Nginx 양쪽 제한을 함께 봐야 합니다.

- Nginx: `docker/nginx/default.conf`의 `client_max_body_size 12m`
- PHP: `docker/php/conf.d/uploads.ini`
- Laravel disk: `src/config/filesystems.php`

R2/S3 호환 스토리지를 사용할 경우 public URL, bucket 권한, path-style endpoint, 공개 파일 삭제 정책을 함께 확인합니다. 사용자 업로드 파일은 MIME 검증, 확장자 검증, 저장 경로 정규화가 필요합니다.

## 인증과 권한

관리자 화면은 `auth`와 `admin` 미들웨어를 통과해야 합니다.

소셜 로그인은 `src/config/services.php`의 Google/Kakao 설정을 사용합니다. redirect URI는 provider 콘솔에 등록된 값과 `src/.env` 값이 일치해야 합니다.

계정 삭제 또는 소셜 연결 해제 기능을 변경할 때는 세션 무효화, 연결 provider 메타데이터, 재로그인 흐름을 함께 검증합니다.

## 네트워크 노출

- MariaDB는 호스트의 `127.0.0.1:3307`에만 publish됩니다.
- Redis는 Compose 파일에서 호스트에 publish하지 않습니다.
- Nginx는 external `proxy-nw` 네트워크를 통해 reverse proxy와 연결됩니다.

운영에서 DB나 Redis를 외부에 직접 노출하지 않습니다. reverse proxy에는 TLS, 보안 헤더, 업로드 크기 제한, 요청 로그 정책을 별도로 적용합니다.

## 배포 전 확인

- `APP_DEBUG=false`인지 확인합니다.
- `.env` 파일이 이미지나 Git에 포함되지 않는지 확인합니다.
- `APP_KEY`가 설정되어 있는지 확인합니다.
- DB, Redis, R2/S3, 소셜 로그인 secret이 운영 값인지 확인합니다.
- `storage`와 `bootstrap/cache` 권한이 Laravel 실행 사용자와 맞는지 확인합니다.
- 마이그레이션, 큐 worker 재시작, 캐시 갱신이 배포 절차에 포함되어 있는지 확인합니다.
