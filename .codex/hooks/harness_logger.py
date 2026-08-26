#!/usr/bin/env python3
"""Append Codex hook events to the TipTipWorld harness workspace log."""

from __future__ import annotations

import datetime as dt
import hashlib
import json
import os
import re
import sys
from pathlib import Path
from typing import Any


MAX_STRING_LENGTH = 2_000
MAX_JSON_LENGTH = 8_000
MAX_SUMMARY_ITEMS = 12
SUMMARY_START = "<!-- HARNESS_SESSION_SUMMARY_START -->"
SUMMARY_END = "<!-- HARNESS_SESSION_SUMMARY_END -->"
SECRET_KEY_RE = re.compile(
    r"(password|passwd|pwd|secret|token|api[_-]?key|access[_-]?key|"
    r"authorization|credential|cookie|session)",
    re.IGNORECASE,
)
SECRET_ASSIGNMENT_RE = re.compile(
    r"(?i)\b(password|passwd|pwd|secret|token|api[_-]?key|access[_-]?key|"
    r"authorization|credential|cookie)([\"']?\s*[:=]\s*[\"']?)([^\"'\s,;})]+)"
)
BEARER_RE = re.compile(r"(?i)\bBearer\s+[A-Za-z0-9._~+/=-]+")
MYSQL_PASSWORD_RE = re.compile(r"(?i)(-p)([^\s\"']+)")
JSON_BLOCK_RE = re.compile(r"```json\n(.*?)\n```", re.DOTALL)
PATCH_FILE_RE = re.compile(r"^\*\*\* (?:Add|Update|Delete) File: (.+)$", re.MULTILINE)
WORK_LOG_PATH_RE = re.compile(r"(\.codex/harness/_workspace/[^\s]+\.md)")

POSITIVE_FEEDBACK_RE = re.compile(
    r"(좋아|좋습니다|맞아|맞습니다|괜찮|완료|잘 ?됐|이대로|만족|승인|"
    r"그렇게 해줘|반영해줘|구현해줘|해줘|굿|thanks|thank you|works)",
    re.IGNORECASE,
)
NEGATIVE_FEEDBACK_RE = re.compile(
    r"(아니|그게 아니|잘못|틀렸|다시|안 ?돼|불만|마음에 안|범위.*벗|"
    r"작동 안|실패|별로|부족|누락|오류|에러)",
    re.IGNORECASE,
)
ADJUSTMENT_FEEDBACK_RE = re.compile(
    r"(근데|그런데|하지만|다만|중요한데|추가|그리고|관련해서|예를 ?들|"
    r"어떤|왜|어떻게|\?)",
    re.IGNORECASE,
)
VALIDATION_COMMAND_RE = re.compile(
    r"(composer test|php artisan test|npm run build|vendor/bin/pint|"
    r"python3 -m json\.tool|git diff --check|php -l|pint)",
    re.IGNORECASE,
)


def repo_root() -> Path:
    return Path(__file__).resolve().parents[2]


def workspace_dir() -> Path:
    override = os.environ.get("HARNESS_LOGGER_WORKSPACE")
    if override:
        return Path(override).resolve()
    return repo_root() / ".codex" / "harness" / "_workspace"


def now() -> dt.datetime:
    return dt.datetime.now().astimezone()


def redact_string(value: str) -> str:
    value = SECRET_ASSIGNMENT_RE.sub(r"\1\2[REDACTED]", value)
    value = BEARER_RE.sub("Bearer [REDACTED]", value)
    value = MYSQL_PASSWORD_RE.sub(r"\1[REDACTED]", value)
    if len(value) > MAX_STRING_LENGTH:
        return value[:MAX_STRING_LENGTH] + "...[truncated]"
    return value


def sanitize(value: Any, key: str | None = None) -> Any:
    if key and SECRET_KEY_RE.search(key):
        return "[REDACTED]"
    if isinstance(value, dict):
        return {str(k): sanitize(v, str(k)) for k, v in value.items()}
    if isinstance(value, list):
        return [sanitize(item) for item in value[:100]]
    if isinstance(value, tuple):
        return [sanitize(item) for item in value[:100]]
    if isinstance(value, str):
        return redact_string(value)
    return value


def compact_json(value: Any) -> str:
    text = json.dumps(sanitize(value), ensure_ascii=False, indent=2, sort_keys=True)
    if len(text) > MAX_JSON_LENGTH:
        return text[:MAX_JSON_LENGTH] + "\n...[truncated]"
    return text


def compact_text(value: Any, limit: int = 140) -> str:
    text = sanitize(str(value)).replace("\n", " ").strip()
    text = re.sub(r"\s+", " ", text)
    if len(text) > limit:
        return text[: limit - 14].rstrip() + "...[truncated]"
    return text


def markdown_cell(value: Any) -> str:
    text = compact_text(value)
    return text.replace("|", "\\|")


def slug_session_id(event: dict[str, Any]) -> str:
    raw = str(event.get("session_id") or event.get("conversation_id") or "")
    if raw:
        return hashlib.sha256(raw.encode("utf-8")).hexdigest()[:12]

    transcript = str(event.get("transcript_path") or "")
    cwd = str(event.get("cwd") or os.getcwd())
    fallback = hashlib.sha256(f"{transcript}:{cwd}".encode("utf-8")).hexdigest()
    return fallback[:12]


def load_session_map(path: Path) -> dict[str, str]:
    if not path.exists():
        return {}
    try:
        data = json.loads(path.read_text(encoding="utf-8"))
    except (OSError, json.JSONDecodeError):
        return {}
    if not isinstance(data, dict):
        return {}
    return {str(k): str(v) for k, v in data.items()}


def save_session_map(path: Path, data: dict[str, str]) -> None:
    path.write_text(
        json.dumps(data, ensure_ascii=False, indent=2, sort_keys=True) + "\n",
        encoding="utf-8",
    )


def log_path_for(event: dict[str, Any], workspace: Path) -> Path:
    session_slug = slug_session_id(event)
    map_path = workspace / ".session-log-map.json"
    session_map = load_session_map(map_path)
    if session_slug not in session_map:
        timestamp = now().strftime("%Y%m%d_%H%M")
        session_map[session_slug] = f"{timestamp}_codex-session-{session_slug}.md"
        save_session_map(map_path, session_map)
    return workspace / session_map[session_slug]


def event_name(event: dict[str, Any]) -> str:
    for key in ("hook_event_name", "event", "event_name", "hookEventName"):
        value = event.get(key)
        if value:
            return str(value)
    if "tool_name" in event:
        return "ToolUse"
    return "Unknown"


def ensure_header(path: Path, event: dict[str, Any]) -> None:
    if path.exists():
        return

    timestamp = now().isoformat(timespec="seconds")
    session = slug_session_id(event)
    cwd = sanitize(str(event.get("cwd") or os.getcwd()))
    transcript = sanitize(str(event.get("transcript_path") or ""))
    path.write_text(
        "\n".join(
            [
                "# 하네스 자동 작업 로그",
                "",
                "## 기본 정보",
                "",
                f"- 생성 시각: {timestamp}",
                f"- 세션: {session}",
                f"- 작업 디렉터리: `{cwd}`",
                f"- transcript_path: `{transcript}`" if transcript else "- transcript_path:",
                "- 기록 방식: Codex project hook",
                "- 민감정보 처리: secret-like key와 token-like value는 마스킹",
                "",
                "## 자동 이벤트 로그",
                "",
            ]
        ),
        encoding="utf-8",
    )


def append_event(path: Path, event: dict[str, Any]) -> None:
    timestamp = now().isoformat(timespec="seconds")
    name = event_name(event)
    tool_name = str(event.get("tool_name") or event.get("tool") or "")

    lines = [
        f"### {timestamp} - {name}",
        "",
    ]
    if tool_name:
        lines.extend([f"- Tool: `{sanitize(tool_name)}`", ""])

    lines.extend(
        [
            "```json",
            compact_json(event),
            "```",
            "",
        ]
    )
    with path.open("a", encoding="utf-8") as handle:
        handle.write("\n".join(lines))


def events_from_log(path: Path) -> list[dict[str, Any]]:
    try:
        text = path.read_text(encoding="utf-8")
    except OSError:
        return []

    events = []
    for match in JSON_BLOCK_RE.finditer(text):
        try:
            data = json.loads(match.group(1))
        except json.JSONDecodeError:
            continue
        if isinstance(data, dict):
            events.append(data)
    return events


def event_prompt(event: dict[str, Any]) -> str:
    for key in ("prompt", "user_prompt", "input", "message"):
        value = event.get(key)
        if isinstance(value, str) and value.strip():
            return value
    return ""


def event_command(event: dict[str, Any]) -> str:
    tool_input = event.get("tool_input")
    if isinstance(tool_input, dict):
        for key in ("command", "cmd", "patch"):
            value = tool_input.get(key)
            if isinstance(value, str) and value.strip():
                return value
    for key in ("command", "cmd", "patch"):
        value = event.get(key)
        if isinstance(value, str) and value.strip():
            return value
    return ""


def tool_status(event: dict[str, Any]) -> str:
    response = str(event.get("tool_response") or event.get("output") or "")
    if "Process exited with code 0" in response or "exit code 0" in response:
        return "성공"
    if "Process exited with code" in response or "exit code" in response:
        return "실패/확인 필요"
    if response:
        return "결과 기록됨"
    return "실행 전 기록"


def user_prompts(events: list[dict[str, Any]]) -> list[str]:
    prompts = []
    for event in events:
        if event_name(event) != "UserPromptSubmit":
            continue
        prompt = event_prompt(event)
        if prompt:
            prompts.append(prompt)
    return prompts


def classify_feedback(next_prompt: str | None) -> tuple[str, str]:
    if not next_prompt:
        return ("미확인", "후속 사용자 반응 없음")

    prompt = compact_text(next_prompt, 220)
    if NEGATIVE_FEEDBACK_RE.search(prompt):
        return ("불만족/수정 요청 추정", prompt)
    if POSITIVE_FEEDBACK_RE.search(prompt):
        return ("만족/승인 추정", prompt)
    if ADJUSTMENT_FEEDBACK_RE.search(prompt):
        return ("부분 만족/추가 확인", prompt)
    return ("후속 요청 있음", prompt)


def prompt_feedback_rows(prompts: list[str]) -> list[str]:
    if not prompts:
        return ["| - | 기록된 사용자 요청 없음 | 미확인 | - |"]

    rows = []
    for index, prompt in enumerate(prompts[:MAX_SUMMARY_ITEMS], start=1):
        next_prompt = prompts[index] if index < len(prompts) else None
        status, evidence = classify_feedback(next_prompt)
        rows.append(
            "| "
            + " | ".join(
                [
                    str(index),
                    markdown_cell(prompt),
                    markdown_cell(status),
                    markdown_cell(evidence),
                ]
            )
            + " |"
        )
    if len(prompts) > MAX_SUMMARY_ITEMS:
        rows.append(
            f"| ... | 추가 요청 {len(prompts) - MAX_SUMMARY_ITEMS}건 | 요약 생략 | 원문 이벤트 로그 참조 |"
        )
    return rows


def command_rows(events: list[dict[str, Any]]) -> list[str]:
    rows = []
    seen = set()
    for event in events:
        command = event_command(event)
        if not command or command in seen:
            continue
        seen.add(command)
        rows.append(
            "| "
            + " | ".join(
                [
                    markdown_cell(str(event.get("tool_name") or event.get("tool") or "Tool")),
                    markdown_cell(command),
                    markdown_cell(tool_status(event)),
                ]
            )
            + " |"
        )
        if len(rows) >= MAX_SUMMARY_ITEMS:
            break
    return rows or ["| - | 기록된 도구 명령 없음 | - |"]


def validation_rows(events: list[dict[str, Any]]) -> list[str]:
    rows = []
    seen = set()
    for event in events:
        command = event_command(event)
        if not command or command in seen or not VALIDATION_COMMAND_RE.search(command):
            continue
        seen.add(command)
        rows.append(
            "| "
            + " | ".join(
                [
                    markdown_cell(command),
                    markdown_cell(tool_status(event)),
                    markdown_cell("원문 이벤트 로그 참조"),
                ]
            )
            + " |"
        )
    return rows or ["| - | 미기록 | 검증 명령이 감지되지 않음 |"]


def changed_files(events: list[dict[str, Any]]) -> list[str]:
    files = []
    seen = set()
    for event in events:
        command = event_command(event)
        response = str(event.get("tool_response") or "")
        for candidate in PATCH_FILE_RE.findall(command):
            if candidate not in seen:
                seen.add(candidate)
                files.append(candidate)
        for candidate in WORK_LOG_PATH_RE.findall(response):
            if candidate not in seen:
                seen.add(candidate)
                files.append(candidate)
    return files


def changed_file_lines(events: list[dict[str, Any]]) -> list[str]:
    files = changed_files(events)
    if not files:
        return ["- 없음 또는 자동 추정 불가"]
    lines = [f"- `{sanitize(path)}`" for path in files[:MAX_SUMMARY_ITEMS]]
    if len(files) > MAX_SUMMARY_ITEMS:
        lines.append(f"- 추가 {len(files) - MAX_SUMMARY_ITEMS}개 파일은 원문 이벤트 로그 참조")
    return lines


def session_summary(events: list[dict[str, Any]]) -> str:
    prompts = user_prompts(events)
    generated_at = now().isoformat(timespec="seconds")
    event_count = len(events)
    stop_count = sum(1 for event in events if event_name(event) == "Stop")

    lines = [
        SUMMARY_START,
        "## 세션 요약",
        "",
        f"- 마지막 요약 갱신: {generated_at}",
        f"- 기록된 이벤트: {event_count}건",
        f"- 세션 종료 이벤트: {stop_count}건",
        "- 요약 방식: 규칙 기반 자동 요약. 정확한 판단 근거는 아래 원문 이벤트 로그를 확인한다.",
        "- 피드백 판정: 다음 사용자 프롬프트의 표현을 근거로 한 추정이며, 명시적 만족도 입력이 없으면 확정값이 아니다.",
        "",
        "### 요청별 피드백 추적",
        "",
        "| # | 사용자 요청 요약 | 결과 상태 추정 | 근거가 된 후속 프롬프트 |",
        "| --- | --- | --- | --- |",
        *prompt_feedback_rows(prompts),
        "",
        "### 주요 도구 사용",
        "",
        "| 도구 | 입력 요약 | 상태 |",
        "| --- | --- | --- |",
        *command_rows(events),
        "",
        "### 반영 내용 추정",
        "",
        *changed_file_lines(events),
        "",
        "### 검증 결과 추정",
        "",
        "| 명령 | 결과 | 비고 |",
        "| --- | --- | --- |",
        *validation_rows(events),
        "",
        "### 추적성 메모",
        "",
        "- 이 요약은 빠른 리뷰용이며, 재현성과 오류 원인 분석을 위해 원문 이벤트 로그를 그대로 보존한다.",
        "- 변경 파일 목록은 hook 이벤트에 기록된 patch와 작업 로그 생성 출력에서 추정한다.",
        "- 기존 사용자 변경과 AI가 만든 변경을 완전히 구분하려면 최종 보고와 git diff를 함께 확인한다.",
        SUMMARY_END,
    ]
    return "\n".join(lines)


def upsert_summary(path: Path, events: list[dict[str, Any]]) -> None:
    try:
        text = path.read_text(encoding="utf-8")
    except OSError:
        return

    summary = session_summary(events)
    if SUMMARY_START in text and SUMMARY_END in text:
        start = text.index(SUMMARY_START)
        end = text.index(SUMMARY_END, start) + len(SUMMARY_END)
        updated = text[:start].rstrip() + "\n\n" + summary + "\n\n" + text[end:].lstrip()
    elif "## 자동 이벤트 로그" in text:
        updated = text.replace("## 자동 이벤트 로그", summary + "\n\n## 자동 이벤트 로그", 1)
    else:
        updated = text.rstrip() + "\n\n" + summary + "\n"

    path.write_text(updated, encoding="utf-8")


def read_event() -> dict[str, Any]:
    raw = sys.stdin.read()
    if not raw.strip():
        return {"hook_event_name": "Unknown", "stdin": ""}
    try:
        data = json.loads(raw)
    except json.JSONDecodeError:
        return {"hook_event_name": "InvalidJson", "stdin": redact_string(raw)}
    if isinstance(data, dict):
        return data
    return {"hook_event_name": "NonObjectJson", "value": data}


def main() -> int:
    try:
        event = read_event()
        workspace = workspace_dir()
        workspace.mkdir(parents=True, exist_ok=True)
        path = log_path_for(event, workspace)
        ensure_header(path, event)
        append_event(path, event)
        if event_name(event) == "Stop":
            upsert_summary(path, events_from_log(path))
    except Exception:
        # Hooks must not block Codex work if logging fails.
        return 0
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
