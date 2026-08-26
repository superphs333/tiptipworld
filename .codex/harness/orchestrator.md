# TipTipWorld Codex 하네스

## 목적

이 하네스는 TipTipWorld 변경 작업을 일관된 역할 기반 엔지니어링 검토로 처리하기 위해 사용한다. Codex에 맞춰 구성되어 있으므로 `AGENTS.md`가 진입점이고, 이 파일은 오케스트레이션 가이드다.

## 사용 시점

기능 개발, 버그 수정, 리팩토링, Blade/UI 변경, 테스트 보강, 운영/보안 검토, 배포 영향 분석, 그리고 "QA만 다시 실행", "UI 부분만 업데이트", "이전 결과 개선" 같은 후속 요청에 이 하네스를 사용한다.

코드 변경이나 검증이 필요 없는 좁은 사실 확인 질문에는 직접 답한다.

## 역할

- Laravel 도메인: `.codex/harness/agents/laravel-domain-engineer.md`
- Blade/UI: `.codex/harness/agents/blade-ux-engineer.md`
- QA 경계 검증: `.codex/harness/agents/qa-boundary-reviewer.md`
- 운영/보안: `.codex/harness/agents/ops-security-reviewer.md`

multi-agent 도구를 사용할 수 있고 작업 범위가 넓다면 관련 도구를 탐색한 뒤 사용한다. 그렇지 않으면 기본 Codex 세션에서 이 역할들을 순차적으로 적용하고 같은 검토 순서를 유지한다.

## 메타 역할

모든 변경 작업은 다음 메타 역할 관점으로 분리한다.

- Researcher: 기존 구조, 영향 범위, 선행 변경, 제약을 조사한다. 파일 수정 없이 읽기와 상태 확인만 수행한다.
- Planner: 작업 범위, 단계, 리스크, 검증 방법을 정리한다. 사용자 승인이 필요한 작업인지 판단한다.
- Implementer: 승인된 범위 안에서만 파일을 수정한다. Laravel 도메인, Blade/UI, 운영/보안 역할 중 주 역할을 지정한다.
- Reviewer: 구현 결과를 독립적으로 검토하고 QA 경계 검증, 테스트 결과, 배포 영향을 확인한다.

저장소 역할 파일은 도메인 전문성을 정의하고, 메타 역할은 실행 순서와 책임 분리를 정의한다. 예를 들어 팁 작성 기능 변경은 Researcher가 라우트/컨트롤러/서비스/뷰를 조사하고, Planner가 계획과 리스크를 제시한 뒤, Implementer가 Laravel 도메인 및 Blade/UI 역할로 수정하고, Reviewer가 QA 경계 검증 역할로 확인한다.

## Plan-first 승인 게이트

사용자가 명시적으로 즉시 구현을 승인하지 않은 모든 변경 요청은 plan-first workflow를 따른다.

1. 파일 수정 전에 관련 구조와 현재 git 상태를 확인한다.
2. 작업 범위, 수정 대상, 리스크, 검증 방법을 계획으로 제시한다.
3. 사용자 승인을 받기 전까지 `apply_patch`, 포맷터, 코드 생성, 파일 생성/삭제, migration 생성 등 파일을 바꾸는 작업을 하지 않는다.
4. 승인이 오면 승인된 범위 안에서만 구현한다.
5. 구현 중 범위가 커지거나 새 위험이 발견되면 멈추고 갱신된 계획 또는 확인 질문을 제시한다.

예외는 사용자가 "바로 반영해줘", "구현해줘", "수정해줘", "테스트 추가해줘"처럼 실행을 명시적으로 승인한 경우다. 이 경우에도 먼저 좁게 조사하고, 관련 없는 파일은 수정하지 않는다.

## 작업 로그

하네스가 적용되는 작업은 `.codex/harness/_workspace/`에 작업 로그를 남긴다. 로그 파일명은 다음 규칙을 따른다.

```text
.codex/harness/_workspace/YYYYMMDD_HHMM_{short-task-name}.md
```

새 로그는 `bash .codex/harness/scripts/new-work-log.sh {short-task-name}`로 만들거나 `.codex/harness/templates/work-log-template.md`를 복사해 작성한다.

로그에는 다음을 기록한다.

- 사용자 프롬프트 요약과 승인 상태
- 사용한 메타 역할, 전문 역할, 워크플로우
- 주요 조사 파일과 명령
- 주요 도구 사용 요약
- 변경 파일
- 검증 명령과 결과
- 배포 영향, secret/env 영향, 남은 리스크

프롬프트 전문이나 secret 값은 그대로 기록하지 않는다. 재현에 필요한 수준으로 요약하고, 민감한 값은 key 이름과 위치만 남긴다.

## Phase 0: 컨텍스트 확인

1. 사용자 요청과 현재 git 상태를 확인한다.
2. 하네스 적용 대상이면 작업 로그를 만들거나 기존 후속 로그를 이어서 사용한다.
3. `.codex/harness/_workspace/`가 존재하고 사용자가 후속 작업을 요청했다면 관련 이전 메모를 먼저 읽는다.
4. 사용자가 관련 없는 새 작업을 요청했다면 기존 workspace 메모는 그대로 두고, 구분되는 파일명으로 새 메모를 만든다.
5. 관련 없는 사용자 변경은 되돌리지 않는다.

Workspace 메모 규칙:

```text
.codex/harness/_workspace/YYYYMMDD_HHMM_{short-task-name}.md
```

## Phase 1: 작업 분류

- 백엔드 도메인 로직, Eloquent, 라우트, 요청, 정책, 마이그레이션: `laravel-domain-engineer`와 `laravel-domain-workflow`를 사용한다.
- Blade, Tailwind/CSS, Alpine, jQuery, Summernote, Tiptap, Vite 자산: `blade-ux-engineer`와 `blade-ui-workflow`를 사용한다.
- Pest 테스트, 회귀 분석, route-controller-service-view shape 확인: `qa-boundary-reviewer`와 `qa-boundary-workflow`를 사용한다.
- Docker, env, storage, R2/S3, Socialite, queue/cache, 배포 위험: `ops-security-reviewer`와 `ops-security-workflow`를 사용한다.

대부분의 실제 변경은 최소 두 역할이 관여한다. 예를 들어 폼 변경은 보통 Laravel 요청 검증, Blade 상태 처리, QA가 함께 필요하다.

## 기능 유형별 필수 경로

- 인증/소셜 로그인: Laravel 도메인 + QA 경계 검증 + 운영/보안
- 팁 작성/수정/삭제: Laravel 도메인 + Blade/UI + QA 경계 검증
- 이미지 업로드/R2: Laravel 도메인 + QA 경계 검증 + 운영/보안
- 댓글/좋아요/북마크/팔로우: Laravel 도메인 + Blade/UI + QA 경계 검증
- 알림/마이페이지: Laravel 도메인 + Blade/UI + QA 경계 검증
- 관리자 화면: Laravel 도메인 + Blade/UI + QA 경계 검증
- Docker/env/config/storage/queue: 운영/보안 + QA 경계 검증
- 순수 Blade/CSS 정리: Blade/UI + 필요한 경우 QA 경계 검증

## Phase 2: 좁게 조사

관련된 가장 작은 경계부터 시작한다.

- 라우트: `src/routes`
- 컨트롤러, 요청, 정책, 서비스, 모델: `src/app`
- 뷰와 자산: `src/resources`
- 테스트: `src/tests`
- 설정과 데이터베이스: `src/config`, `src/database`
- Docker 스택: `docker-compose.yml`, `docker/`
- 의존성: `src/composer.json`, `src/package.json`

새 추상화를 도입하기 전에 저장소의 기존 패턴을 우선 사용한다.

조사 결과는 계획에 반영한다. plan-first 승인 게이트가 적용되는 작업에서는 이 시점까지 파일을 수정하지 않는다.

## Phase 2.5: 계획 승인

plan-first workflow가 적용되는 작업은 구현 전에 다음을 사용자에게 제시하고 승인을 받는다.

- 작업 범위와 제외 범위
- 수정할 파일 또는 모듈
- 단계별 구현 계획
- 리스크와 롤백/완화 방법
- 검증 명령

승인 전에는 구현으로 넘어가지 않는다. 사용자가 계획 수정을 요청하면 계획을 갱신하고 다시 확인한다.

## Phase 3: 구현

1. 사용자 승인 여부와 승인된 범위를 확인한다.
2. 주 역할이 변경을 주도한다.
3. 넓게 편집하기 전에 보조 역할을 참여시켜 경계면을 확인한다.
4. Laravel 코드 추가/수정 시 `.codex/harness/workflows/laravel-domain-workflow.md`의 `Laravel 모범 코드 규칙`과 `TipTipWorld 로컬 규칙`을 적용한다.
5. 컨트롤러는 얇게 유지하고, 재사용 가능한 Laravel 동작은 서비스, 요청, 정책, 모델로 이동한다.
6. UI 변경은 기존 Blade 컴포넌트, CSS 파일, JS 컴포넌트 경계와 맞춘다.
7. env, migration, storage, queue, 외부 provider 변경은 배포 영향이 있는 변경으로 취급한다.
8. 작업 로그에 변경 파일과 주요 도구 사용 요약을 갱신한다.

## Phase 4: 검증

위험도에 따라 검증 방법을 선택한다.

- PHP 동작: `src/`에서 `composer test`를 실행하거나, 범위를 좁혀 `php artisan test --filter=...`를 실행한다.
- PHP 포맷팅: 수정한 PHP 파일에는 `vendor/bin/pint` 실행을 검토한다.
- 프론트엔드 자산: `src/`에서 `npm run build`를 실행한다.
- Docker/config 변경: 필요한 env key, port, volume, storage 권한, 배포 메모를 확인한다.

명령을 실행할 수 없다면 이유를 기록하고 가능한 가장 강한 정적 검증 결과를 제공한다.

## Phase 5: 보고

최종 인계에는 다음을 포함한다.

- 변경한 내용
- 검증한 내용
- migration, queue, storage, env, 배포 영향
- 남은 위험이나 사용자 입력이 필요한 결정
- 작업 로그 경로

## 최종 보고 템플릿

- 변경 요약:
- 변경 파일:
- 검증:
- 배포 영향:
- 남은 위험:
- 작업 로그:

## 에러 처리

- 테스트/빌드 실패는 한 번 원인을 진단하고, 실용적인 범위 안에서 수정한다.
- secret이나 외부 서비스가 없을 때는 secret 값을 출력하지 않는다. 필요한 key 이름과 설정 위치만 언급한다.
- 증거가 충돌하면 양쪽 관찰을 모두 남기고 더 안전한 가정을 명시한다.

## 테스트 시나리오

정상 흐름: 팁 폼에 새 공개 범위 옵션을 추가하는 요청은 Laravel enum/request/service 검토, Blade 폼 업데이트, Feature 테스트, 프론트엔드 빌드 검증을 트리거해야 한다.

에러 흐름: R2 업로드 실패는 config/env/storage-service 점검, 안전한 secret 처리, 테스트 가능성 검토, 배포 체크리스트 출력을 트리거해야 한다.
