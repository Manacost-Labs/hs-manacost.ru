# Real-content test cases

Exercise each changed surface with:

- a long Cyrillic headline containing narrow and wide letters, punctuation, quotes, and an unbroken Latin token;
- a short title, missing excerpt, long author name, multiple categories, and long breadcrumb;
- portrait, landscape, transparent, missing, slow, and oversized images;
- ad present, ad absent, late ad response, blocked third party, responsive embed, and long deck code;
- comments empty/populated, search empty/results, pagination first/middle/last, and a failed asynchronous request;
- anonymous, authenticated admin-bar, keyboard-only, touch-only, portrait, and landscape states;
- browser zoom at 200% and text enlargement without clipping or loss of actions.

Use staging fixtures or read-only production-like content. Never mutate production content just to obtain a screenshot.
