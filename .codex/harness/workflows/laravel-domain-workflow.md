# Laravel 도메인 워크플로우

Laravel 12 백엔드 변경에 이 워크플로우를 사용한다. 대상은 라우트, 컨트롤러, 요청, 정책, 서비스, Eloquent 모델, 마이그레이션, config, Pest 테스트다.

## 단계

1. 라우트와 미들웨어에서 시작한다.
2. 컨트롤러에서 Form Request, Policy, Service, Model, View 의존성으로 따라간다.
3. 컨트롤러 변경은 작게 유지하고, 재사용 가능한 동작은 기존 service/request/policy 경계로 이동한다.
4. DB 변경 시 필요에 따라 마이그레이션, casts/relations/fillable, factory/seeder, 테스트를 갱신한다.
5. 동작 위험도에 따라 Feature/Unit 테스트를 추가하거나 갱신한다.

## Laravel 모범 코드 규칙

- 컨트롤러는 요청 흐름 조율만 담당한다. 비즈니스 로직은 Service, Action 성격의 클래스, Model method, Policy, Form Request 중 가장 자연스러운 경계로 분리한다.
- 입력 검증은 컨트롤러에서 직접 처리하지 않고 Form Request에 둔다. 단순한 `request()->validate()`는 임시 코드나 매우 작은 변경에서만 허용한다.
- 권한 판단은 컨트롤러 조건문보다 Policy 또는 Gate를 우선 사용한다.
- Eloquent query가 중복되거나 읽기 어렵다면 local scope, 전용 query method, service method로 정리한다.
- 목록/상세 화면에서 relation을 사용하는 경우 N+1 가능성을 확인하고 필요한 relation은 명시적으로 eager load한다.
- 여러 DB 변경이 하나의 사용자 동작에 묶이면 transaction 사용을 검토한다.
- 외부 서비스, storage, image upload, social login은 예외 처리와 실패 시 사용자 경험을 함께 설계한다.
- enum, value object, DTO는 실제 중복, 불변 규칙, 데이터 shape 안정성을 줄일 때만 추가한다.
- config 값은 코드에 직접 박지 않고 `src/config`와 env key를 통해 주입한다. 새 필수 env key는 `src/.env.example` 갱신 여부를 확인한다.
- route, controller, request, policy, service, model, view 사이의 데이터 shape이 일치하는지 확인한다.
- 인증, 권한, 팁 작성/수정/삭제, 이미지 처리, 소셜 로그인, 알림 변경에는 Pest 테스트를 추가하거나 갱신한다.
- PHP 파일 변경 후에는 변경 범위에 따라 `vendor/bin/pint` 실행을 검토한다.

## 로컬 패턴

- Tip 읽기/쓰기: `App\Services\Tip\*`, `TipReadService`, `TipWriteService`
- Media: `App\Services\Media\*`
- Validation: `src/app/Http/Requests`
- Authorization: `src/app/Policies`
- Tests: `src/tests/Feature`, `src/tests/Unit`

## TipTipWorld 로컬 규칙

- 팁 읽기/쓰기 로직은 먼저 `App\Services\Tip\*`, `TipReadService`, `TipWriteService`의 기존 책임과 맞춘다.
- 이미지 관련 로직은 `App\Services\Media\*` 아래 기존 서비스 경계를 따른다.
- Form Request는 `src/app/Http/Requests`에 두고, `StoreTipRequest`, `UpdateTipRequest`, `DestroyTipRequest`처럼 동작 중심으로 이름 짓는다.
- 권한이 필요한 팁 변경은 `src/app/Policies/TipPolicy.php` 패턴과 맞춘다.
- Blade에 전달할 표시용 값이 복잡하면 컨트롤러에서 직접 조립하지 말고 service 또는 presenter 사용을 검토한다.
- 알림, 팔로우, 댓글, 반응 기능은 관련 count/cache/notification 부작용을 함께 확인한다.
- Socialite provider 변경은 `src/config/social-auth.php`, `src/config/services.php`, 프로필 연결/해제 흐름을 함께 확인한다.
- Media/R2 변경은 storage fake 테스트 가능성, 공개 URL, 삭제 동작, env key를 함께 확인한다.

## 리팩토링 판단 기준

- 같은 로직이 2곳 이상 반복되면 service, scope, helper method 분리를 검토한다.
- 컨트롤러 메서드가 validation/authorization 이후에도 여러 도메인 결정을 직접 처리하면 service로 분리한다.
- 단순 CRUD 연결만 있는 경우 불필요한 service를 만들지 않는다.
- Model scope는 query 조건 재사용에만 사용하고, 사용자 흐름 전체를 담지 않는다.
- Presenter는 화면 표시용 값 조립이 반복될 때만 사용한다.
- DTO나 value object는 배열 shape 불안정, 반복 변환, 불변 규칙을 실제로 줄일 때만 사용한다.
- 큰 리팩토링은 기능 변경과 분리한다. 기능 수정 중 발견한 구조 개선은 필요한 최소 범위만 반영한다.

## 변경 전 체크리스트

- 관련 route와 middleware를 확인했는가?
- controller에서 호출하는 Form Request, Policy, Service, Model 관계를 따라갔는가?
- 기존 서비스나 scope로 해결 가능한데 새 추상화를 만들고 있지는 않은가?
- 인증/권한/validation 실패 경로가 사용자 흐름과 맞는가?
- migration, env, storage, queue, 외부 provider 영향이 있는가?

## 변경 후 체크리스트

- controller, request, policy, service, model, view의 field name과 데이터 shape이 일치하는가?
- 목록/상세 화면에 N+1 가능성이 생기지 않았는가?
- DB 쓰기 흐름에 transaction이 필요한데 빠져 있지 않은가?
- 실패 시 사용자에게 안전하고 이해 가능한 응답이 돌아가는가?
- 변경 위험도에 맞는 Pest 테스트를 추가하거나 갱신했는가?

## 검증

- 집중된 동작 검증: `php artisan test --filter=...`
- 더 넓은 백엔드 변경: `composer test`
- PHP 파일을 수정했을 때 포맷팅: `vendor/bin/pint`
