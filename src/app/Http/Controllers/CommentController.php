<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Tip;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CommentController extends Controller
{
    /**
     * 댓글 목록 조회
     *
     * 설계 포인트:
     * - depth 0(부모), depth 1(대댓글) 2단계 구조만 사용
     * - 재귀 없이 2-pass(분리 -> 결합)로 트리를 구성
     * - status가 active/deleted 인 댓글만 노출 (hidden 제외)
     */
    public function commentList(int $tip_id): JsonResponse
    {
        // 대상 팁이 없으면 404
        Tip::findOrFail($tip_id);

        // 권한 플래그 계산에 사용할 로그인 사용자
        $authUser = Auth::user();
        $authUserId = $authUser?->id;
        $isAdmin = $authUser?->isAdmin() ?? false;

        // 평면 댓글 목록 조회
        $query = Comment::query()
            ->with([
                'user:id,name,profile_image_path',
                'parent:id,user_id,body,status',
                'parent.user:id,name',
                'replyTo:id,user_id,body,status,parent_id',
                'replyTo.user:id,name',
            ]);

        if ($authUserId !== null) {
            $query->with([
                'likedUsers' => fn ($likeQuery) => $likeQuery->whereKey($authUserId),
            ]);
        }

        $flat = $query
            ->where('tip_id', $tip_id)
            ->whereIn('status', ['active', 'deleted'])
            ->where('depth', '<=', 1)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(fn (Comment $comment) => $this->serializeComment($comment, $authUserId, $isAdmin))
            ->all();

        // 2단계 트리 구성용 버킷
        $rootsById = []; // 부모 댓글 저장(id => node)
        $rootOrder = []; // 부모 댓글 출력 순서 유지
        $repliesByParent = []; // parent_id 기준 대댓글 묶음

        foreach ($flat as $node) {
            if ($node['parent_id'] === null) {
                $node['children'] = [];
                $rootsById[$node['id']] = $node;
                $rootOrder[] = $node['id'];
                continue;
            }

            $repliesByParent[$node['parent_id']][] = $node;
        }

        // 부모 댓글에 대댓글 결합
        // 부모가 없는 고아 대댓글은 여기서 자동 제외(root 승격 방지)
        $comments = [];

        foreach ($rootOrder as $rootId) {
            $root = $rootsById[$rootId];
            $root['children'] = $repliesByParent[$rootId] ?? [];
            $comments[] = $root;
        }

        return response()->json([
            'success' => true,
            'comments' => $comments,
        ]);
    }

    /**
     * 댓글/답글 등록
     *
     * 규칙:
     * - parent_id가 없으면 일반 댓글(depth=0)
     * - parent_id가 있으면 답글(depth=1)
     * - depth는 2단계(0/1)만 유지하고, 대댓글 대상은 reply_to_id로 관리
     * - deleted/hidden 댓글에는 답글 작성 불가
     */
    public function commentAdd(int $tip_id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'comment' => ['required', 'string', 'max:500'],
            'parent_id' => ['nullable', 'integer', 'min:1'],
            'reply_to_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $body = trim((string) $validated['comment']);
        if ($body === '') {
            return response()->json(['message' => '댓글을 입력해주세요.'], 422);
        }

        Tip::findOrFail($tip_id);

        $parentIdInput = isset($validated['parent_id']) ? (int) $validated['parent_id'] : null;
        $replyToIdInput = isset($validated['reply_to_id']) ? (int) $validated['reply_to_id'] : null;

        $parentId = null;
        $replyToId = null;
        $depth = 0;

        if ($parentIdInput === null && $replyToIdInput !== null) {
            return response()->json([
                'message' => 'reply_to_id는 parent_id와 함께 전달되어야 합니다.',
            ], 422);
        }

        if ($parentIdInput !== null) {
            $parent = Comment::query()
                ->where('tip_id', $tip_id)
                ->where('status', 'active')
                ->findOrFail($parentIdInput);

            if ((int) $parent->depth !== 0) {
                return response()->json([
                    'message' => 'parent_id는 원댓글 ID여야 합니다.',
                ], 422);
            }

            $parentId = (int) $parent->id;
            $depth = 1;

            if ($replyToIdInput === null) {
                $replyToId = $parentId;
            } else {
                $replyTarget = Comment::query()
                    ->where('tip_id', $tip_id)
                    ->where('status', 'active')
                    ->findOrFail($replyToIdInput);

                $replyTargetParentId = $replyTarget->parent_id === null ? null : (int) $replyTarget->parent_id;
                $isSameThread = (int) $replyTarget->id === $parentId || $replyTargetParentId === $parentId;

                if (! $isSameThread) {
                    return response()->json([
                        'message' => '같은 댓글 스레드 내에서만 답글을 달 수 있습니다.',
                    ], 422);
                }

                $replyToId = (int) $replyTarget->id;
            }
        }

        $comment = Comment::create([
            'tip_id' => $tip_id,
            'user_id' => Auth::id(),
            'body' => $body,
            'parent_id' => $parentId,
            'reply_to_id' => $replyToId,
            'depth' => $depth,
            'status' => 'active',
        ]);

        if ($parentId !== null) {
            $this->syncReplyCount((int) $parentId);
        }

        $authUser = Auth::user();
        $authUserId = $authUser?->id;
        $isAdmin = $authUser?->isAdmin() ?? false;

        $relations = [
            'user:id,name,profile_image_path',
            'parent:id,user_id,body,status',
            'parent.user:id,name',
            'replyTo:id,user_id,body,status,parent_id',
            'replyTo.user:id,name',
        ];

        if ($authUserId !== null) {
            $relations['likedUsers'] = fn ($likeQuery) => $likeQuery->whereKey($authUserId);
        }

        $comment->load($relations);

        return response()->json([
            'success' => true,
            'message' => '댓글이 등록되었습니다.',
            'comment' => $this->serializeComment($comment, $authUserId, $isAdmin),
        ], 201);
    }

    /**
     * 댓글 삭제
     *
     * 정책:
     * - 물리 삭제(delete)하지 않고 status='deleted'로 상태만 변경
     * - 본문은 고정 문구로 마스킹
     */
    public function commentDelete(int $comment_id): JsonResponse
    {
        $comment = Comment::query()->findOrFail($comment_id);

        $user = Auth::user();
        $isOwner = (int) $comment->user_id === (int) $user->id;
        $isAdmin = $user?->isAdmin() ?? false;

        if (! $isOwner && ! $isAdmin) {
            return response()->json(['message' => '삭제 권한이 없습니다.'], 403);
        }

        if ($comment->status !== 'deleted') {
            $comment->update([
                'status' => 'deleted',
                'body' => '삭제된 댓글입니다.',
            ]);
        }

        // 내가 답글이면 부모의 active 자식 수 캐시 갱신
        if ($comment->parent_id !== null) {
            $this->syncReplyCount((int) $comment->parent_id);
        }

        // 내가 부모 댓글이면 내 reply_count도 갱신
        if ((int) $comment->depth === 0) {
            $this->syncReplyCount((int) $comment->id);
        }

        return response()->json([
            'success' => true,
            'comment_id' => (int) $comment->id,
            'status' => 'deleted',
        ]);
    }

    /**
     * 댓글 수정
     *
     * 정책:
     * - 본인 또는 관리자만 수정 가능
     * - active 상태 댓글만 수정 가능
     */
    public function commentUpdate(int $comment_id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'comment' => ['required', 'string', 'max:500'],
        ]);

        $body = trim((string) $validated['comment']);
        if ($body === '') {
            return response()->json(['message' => '댓글을 입력해주세요.'], 422);
        }

        $comment = Comment::query()->findOrFail($comment_id);

        $user = Auth::user();
        $isOwner = (int) $comment->user_id === (int) $user->id;
        $isAdmin = $user?->isAdmin() ?? false;

        if (! $isOwner && ! $isAdmin) {
            return response()->json(['message' => '수정 권한이 없습니다.'], 403);
        }

        if ($comment->status !== 'active') {
            return response()->json(['message' => '삭제되었거나 숨김 처리된 댓글은 수정할 수 없습니다.'], 422);
        }

        $comment->update([
            'body' => $body,
        ]);

        $authUserId = $user?->id;
        $relations = [
            'user:id,name,profile_image_path',
            'parent:id,user_id,body,status',
            'parent.user:id,name',
            'replyTo:id,user_id,body,status,parent_id',
            'replyTo.user:id,name',
        ];

        if ($authUserId !== null) {
            $relations['likedUsers'] = fn ($likeQuery) => $likeQuery->whereKey($authUserId);
        }

        $comment->load($relations);

        return response()->json([
            'success' => true,
            'message' => '댓글이 수정되었습니다.',
            'comment' => $this->serializeComment($comment, $authUserId, $isAdmin),
        ]);
    }

    /**
     * 댓글 좋아요 토글
     */
    public function commentLike(int $comment_id): JsonResponse
    {
        $comment = Comment::query()->findOrFail($comment_id);

        if ($comment->status !== 'active') {
            return response()->json(['message' => '삭제되었거나 숨김 처리된 댓글에는 좋아요를 누를 수 없습니다.'], 422);
        }

        $userId = Auth::id();
        $changed = $comment->likedUsers()->toggle($userId);
        $liked = ! empty($changed['attached']);

        $likeCount = $comment->likedUsers()->count();
        $comment->update(['like_count' => $likeCount]);

        return response()->json([
            'success' => true,
            'comment_id' => (int) $comment->id,
            'liked' => $liked,
            'like_count' => $likeCount,
        ]);
    }

    /**
     * 부모 댓글의 active 대댓글 개수를 reply_count에 반영
     */
    private function syncReplyCount(int $parentId): void
    {
        $count = Comment::query()
            ->where('parent_id', $parentId)
            ->where('status', 'active')
            ->count();

        Comment::query()
            ->whereKey($parentId)
            ->update(['reply_count' => $count]);
    }

    /**
     * 모델 -> API 공통 스키마
     *
     * commentList/commentAdd 응답을 같은 shape으로 유지해서
     * 프론트 렌더 코드를 단순화한다.
     */
    private function serializeComment(Comment $comment, ?int $authUserId, bool $isAdmin): array
    {
        $isDeleted = $comment->status === 'deleted';
        $replyTarget = $comment->replyTo ?? $comment->parent;
        $isLiked = false;

        if ($authUserId !== null) {
            if ($comment->relationLoaded('likedUsers')) {
                $isLiked = $comment->likedUsers->contains('id', $authUserId);
            } else {
                $isLiked = $comment->likedUsers()->where('user_id', $authUserId)->exists();
            }
        }

        $replyToId = $comment->reply_to_id === null ? null : (int) $comment->reply_to_id;
        if ($replyToId === null && (int) ($comment->depth ?? 0) === 1 && $comment->parent_id !== null) {
            $replyToId = (int) $comment->parent_id;
        }

        $replyToUserName = null;
        $replyToBodyPreview = null;

        if ($replyTarget !== null) {
            $replyToUserName = $replyTarget->user?->name ?? '작성자 미상';

            $replyTargetBody = $replyTarget->status === 'deleted'
                ? '삭제된 댓글입니다.'
                : (string) $replyTarget->body;
            $normalizedBody = trim((string) preg_replace('/\s+/u', ' ', $replyTargetBody));

            if ($normalizedBody !== '') {
                $replyToBodyPreview = Str::limit($normalizedBody, 24, '...');
            }
        }

        return [
            'id' => (int) $comment->id,
            'tip_id' => (int) $comment->tip_id,
            'user_id' => (int) $comment->user_id,
            'user_name' => $comment->user?->name ?? '작성자 미상',
            'user_profile_image_url' => $comment->user?->profile_image_url ?? asset('images/avatar-default.svg'),
            'body' => $isDeleted ? '삭제된 댓글입니다.' : (string) $comment->body,
            'created_at' => $comment->created_at?->toISOString(),
            'like_count' => (int) ($comment->like_count ?? 0),
            'reply_count' => (int) ($comment->reply_count ?? 0),
            'depth' => (int) ($comment->depth ?? 0),
            'parent_id' => $comment->parent_id === null ? null : (int) $comment->parent_id,
            'reply_to_id' => $replyToId,
            'reply_to_user_name' => $replyToUserName,
            'reply_to_body_preview' => $replyToBodyPreview,
            'status' => (string) $comment->status,
            'is_deleted' => $isDeleted,
            'is_liked' => $isLiked,
            'can_like' => ! $isDeleted,
            'can_reply' => ! $isDeleted && (int) ($comment->depth ?? 0) <= 1,
            'can_edit' => ! $isDeleted
                && $authUserId !== null
                && ((int) $comment->user_id === $authUserId || $isAdmin),
            'can_delete' => ! $isDeleted
                && $authUserId !== null
                && ((int) $comment->user_id === $authUserId || $isAdmin),
            'children' => [],
        ];
    }
}
