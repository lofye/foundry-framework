#!/usr/bin/env bash
set -euo pipefail

OUTPUT_FILE="pre-canonical-specs.md"
ORDER_FILE="ordered-files.txt"
SOURCE_DIR="historical-specs"

SEPARATOR="
=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
"

# Start fresh
: > "$OUTPUT_FILE"

first=1

while IFS= read -r file; do
  # Do not skip anything — even blank lines are treated as filenames
  INPUT_PATH="$SOURCE_DIR/$file"

  # Append separator *between* files, not before the first
  if [[ $first -eq 0 ]]; then
    printf "%s\n" "$SEPARATOR" >> "$OUTPUT_FILE"
  fi

  first=0

  # Append file contents exactly as-is
  cat "$INPUT_PATH" >> "$OUTPUT_FILE"

done < "$ORDER_FILE"

echo "Done. Output written to $OUTPUT_FILE"
