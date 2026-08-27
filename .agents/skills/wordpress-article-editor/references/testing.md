# Editorial regression testing

## Content integrity fixture

Create or reuse a staging draft containing formatted text, a link, list, captioned image, featured image, deck shortcode, spoiler, separator and tooltip metadata. Record `post_content` and relevant metadata hashes before a no-op edit/save.

## Required flow

1. Open the editor as a representative editorial role and as an administrator.
2. Confirm all expected panels and toolbar buttons appear once and only once.
3. Enter text, wait for autosave and reload/recover the draft.
4. Save a draft; verify data remains after validation or network errors.
5. Preview while logged in and confirm preview is not publicly cached.
6. Restore the preceding revision and verify content plus owned metadata.
7. Upload two images with the same basename; confirm distinct attachment/object URLs and no older image changes.
8. Insert/edit each affected shortcode or editorial control, reopen the article and verify frontend rendering.
9. Publish/update; verify cache invalidation, article image delivery, canonical, ads and view counting.
10. Repeat critical interaction using keyboard only and at 320 px.

## Failure criteria

Do not ship when a no-op save rewrites unrelated content, a control vanishes for the intended role, autosave/revisions lose data, upload state is silent, preview leaks through cache, a duplicate filename overwrites media, or frontend output differs unexpectedly from preview.

Use project automated checks for code and a real browser for the operator workflow. A screenshot does not prove saving, recovery or accessibility.
