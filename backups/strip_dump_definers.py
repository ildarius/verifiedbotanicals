#!/usr/bin/env python3
"""Strip MySQL DEFINER fragments from SQL dumps for safer import."""

from __future__ import annotations

import gzip
import re
import sys
from pathlib import Path


SCRIPT_DIR = Path(__file__).resolve().parent
GZIP_MAGIC = b"\x1f\x8b"

PATTERN_DEFINER_AND_SECURITY = re.compile(
    r"/\*!\d+\s+DEFINER=`[^`]+`@`[^`]+`\s+SQL\s+SECURITY\s+DEFINER\s*\*/"
)
PATTERN_DEFINER_ONLY = re.compile(
    r"/\*!\d+\s+DEFINER=`[^`]+`@`[^`]+`\s*\*/"
)
PATTERN_SQL_SECURITY_ONLY = re.compile(r"SQL\s+SECURITY\s+DEFINER")


def prompt_for_input_file() -> Path:
    raw = input(
        "Enter DB dump filename (.sql or .sql.gz, relative to backups/ or absolute path): "
    ).strip()
    if not raw:
        raise ValueError("No filename provided.")

    candidate = Path(raw).expanduser()
    if candidate.is_absolute():
        return candidate
    return SCRIPT_DIR / candidate


def is_gzip_file(path: Path) -> bool:
    if path.suffix == ".gz":
        return True
    with path.open("rb") as handle:
        return handle.read(2) == GZIP_MAGIC


def read_dump_text(path: Path) -> str:
    if is_gzip_file(path):
        with gzip.open(path, "rt", encoding="utf-8", errors="replace", newline="") as handle:
            return handle.read()
    with path.open("rt", encoding="utf-8", errors="replace", newline="") as handle:
        return handle.read()


def build_output_path(input_path: Path) -> Path:
    name = input_path.name
    if name.endswith(".sql.gz"):
        stripped_name = name[:-3]
    elif name.endswith(".gz"):
        stripped_name = f"{input_path.stem}.sql"
    elif name.endswith(".sql"):
        stripped_name = name
    else:
        stripped_name = f"{name}.sql"
    return input_path.with_name(f"DEFINER_STRIPPED_{stripped_name}")


def find_line_numbers(text: str, needle: str) -> list[int]:
    return [
        line_number
        for line_number, line in enumerate(text.splitlines(), start=1)
        if needle in line
    ]


def main() -> int:
    try:
        input_path = prompt_for_input_file()
    except ValueError as exc:
        print(f"Error: {exc}", file=sys.stderr)
        return 1

    if not input_path.exists():
        print(f"Error: file not found: {input_path}", file=sys.stderr)
        return 1

    if not input_path.is_file():
        print(f"Error: not a file: {input_path}", file=sys.stderr)
        return 1

    source_text = read_dump_text(input_path)

    transformed_text, combined_count = PATTERN_DEFINER_AND_SECURITY.subn("", source_text)
    transformed_text, definer_only_count = PATTERN_DEFINER_ONLY.subn("", transformed_text)
    transformed_text, sql_security_count = PATTERN_SQL_SECURITY_ONLY.subn("", transformed_text)

    total_removed = combined_count + definer_only_count + sql_security_count
    output_path = build_output_path(input_path)
    output_path.write_text(transformed_text, encoding="utf-8", newline="")

    remaining_definer_lines = find_line_numbers(transformed_text, "DEFINER=")
    remaining_security_lines = find_line_numbers(transformed_text, "SQL SECURITY DEFINER")

    print(f"Input:  {input_path}")
    print(f"Output: {output_path}")
    print()
    print("Removed fragments:")
    print(f"  definer-only comments: {definer_only_count}")
    print(f"  definer+sql-security comments: {combined_count}")
    print(f"  leftover SQL SECURITY DEFINER phrases: {sql_security_count}")

    if total_removed == 0:
        print("Warning: zero matches were removed.", file=sys.stderr)

    print()
    if remaining_definer_lines or remaining_security_lines:
        print("Post-write scan found remaining fragments:", file=sys.stderr)
        if remaining_definer_lines:
            print(
                "  remaining DEFINER= lines: "
                + ", ".join(str(line) for line in remaining_definer_lines),
                file=sys.stderr,
            )
        if remaining_security_lines:
            print(
                "  remaining SQL SECURITY DEFINER lines: "
                + ", ".join(str(line) for line in remaining_security_lines),
                file=sys.stderr,
            )
        return 2

    print("Post-write scan: no remaining DEFINER= fragments found.")
    print("Post-write scan: no remaining SQL SECURITY DEFINER phrases found.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
