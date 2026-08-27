# Media recovery

## Recovery decision

1. Freeze mutations for the affected path and record current hashes.
2. Resolve every post/attachment referencing the path; one file can affect multiple old articles.
3. Search independent S3 backup/object versions, host backups and attachment derivatives for a historical candidate.
4. Restore candidates to a temporary location and validate magic MIME, dimensions, decoding and SHA256.
5. Map the recovered file to the exact historical attachment/post before publication.
6. Restore the source first, regenerate sidecars with `hs-local-image-optimizer`, verify offload, then target-purge affected URLs.

Do not infer correctness from a plausible card image or filename. If no verified historical source exists, report the unresolved attachments instead of silently substituting content.

## Deletion gate

Local originals may be removed only when an approved storage policy explicitly allows it and a restore drill proves the exact object plus metadata are recoverable. The current safe default is to preserve sources and treat S3 offload as delivery/capacity infrastructure, not permission to delete originals.
