import { decodeText, localMediaUrl, sanitizeArticleHtml } from "./content.ts";

const API_ROOT = (process.env.WORDPRESS_API_URL ?? "https://hs-manacost.ru/wp-json/wp/v2").replace(/\/$/, "");
const VIP_CATEGORY_ID = 2006;
const CACHE_SECONDS = 120;
const WORDPRESS_TIMEOUT_MS = 8_000;
const ARCHIVE_PAGE_SIZE = 12;

type Rendered = { rendered: string };
type Media = {
  source_url?: string;
  alt_text?: string;
  media_details?: { sizes?: Record<string, { source_url?: string }> };
};
type Term = { id: number; name: string; slug: string; taxonomy?: string };

type WpPost = {
  id: number;
  date: string;
  modified: string;
  slug: string;
  link: string;
  title: Rendered;
  excerpt: Rendered;
  content?: Rendered;
  categories: number[];
  featured_media: number;
  _embedded?: {
    "wp:featuredmedia"?: Media[];
    "wp:term"?: Term[][];
  };
};

export type Story = {
  id: number;
  slug: string;
  sourceUrl: string;
  title: string;
  excerpt: string;
  content: string;
  date: string;
  modified: string;
  image: string | null;
  imageAlt: string;
  categories: Term[];
};

export type Category = Term & { count: number; description?: string };
export type CategoryStories = { stories: Story[]; hasNext: boolean };

type NextFetchInit = RequestInit & { next: { revalidate: number } };
type WpFetchOptions = {
  fetcher?: (input: URL, init: NextFetchInit) => Promise<Response>;
  timeoutMs?: number;
};

function getImage(media?: Media): string | null {
  const sizes = media?.media_details?.sizes;
  const source = sizes?.large?.source_url ?? sizes?.medium_large?.source_url ?? media?.source_url ?? "";
  return localMediaUrl(source) || null;
}

function normalizePost(post: WpPost, media?: Media, categories: Term[] = []): Story {
  return {
    id: post.id,
    slug: decodeURIComponent(post.slug),
    sourceUrl: post.link,
    title: decodeText(post.title.rendered),
    excerpt: decodeText(post.excerpt.rendered),
    content: sanitizeArticleHtml(post.content?.rendered ?? ""),
    date: post.date,
    modified: post.modified,
    image: getImage(media),
    imageAlt: media?.alt_text ? decodeText(media.alt_text) : decodeText(post.title.rendered),
    categories,
  };
}

export async function wpFetch<T>(
  path: string,
  params: Record<string, string | number> = {},
  options: WpFetchOptions = {},
): Promise<T> {
  const url = new URL(`${API_ROOT}/${path}`);
  Object.entries(params).forEach(([key, value]) => url.searchParams.set(key, String(value)));
  const signal = AbortSignal.timeout(options.timeoutMs ?? WORDPRESS_TIMEOUT_MS);
  let response: Response;
  try {
    response = await (options.fetcher ?? fetch)(url, {
      headers: { Accept: "application/json", "User-Agent": "Manacost-v2/0.1 (+https://v2.hs-manacost.ru)" },
      next: { revalidate: CACHE_SECONDS },
      signal,
    });
  } catch (error) {
    if (signal.aborted) throw new Error("WordPress REST request timed out", { cause: error });
    throw error;
  }
  if (!response.ok) throw new Error(`WordPress REST returned ${response.status}`);
  return response.json() as Promise<T>;
}

const postParams = {
  categories_exclude: VIP_CATEGORY_ID,
  status: "publish",
};

const listFields = "id,date,modified,slug,link,title,excerpt,categories,featured_media";
const detailFields = `${listFields},content`;

async function hydratePosts(posts: WpPost[]): Promise<Story[]> {
  const mediaIds = [...new Set(posts.map((post) => post.featured_media).filter(Boolean))];
  const categoryIds = [...new Set(posts.flatMap((post) => post.categories).filter((id) => id !== VIP_CATEGORY_ID))];
  const [media, categories] = await Promise.all([
    mediaIds.length
      ? wpFetch<(Media & { id: number })[]>("media", { include: mediaIds.join(","), per_page: Math.min(mediaIds.length, 100), _fields: "id,source_url,alt_text,media_details" })
      : Promise.resolve([]),
    categoryIds.length
      ? wpFetch<Term[]>("categories", { include: categoryIds.join(","), per_page: Math.min(categoryIds.length, 100), _fields: "id,name,slug,taxonomy" })
      : Promise.resolve([]),
  ]);
  const mediaById = new Map(media.map((item) => [item.id, item]));
  const categoriesById = new Map(categories.map((item) => [item.id, item]));
  return posts.map((post) => normalizePost(
    post,
    mediaById.get(post.featured_media),
    post.categories.map((id) => categoriesById.get(id)).filter((item): item is Term => Boolean(item))
  ));
}

export async function getLatestStories(limit = 13): Promise<Story[]> {
  const posts = await wpFetch<WpPost[]>("posts", { ...postParams, per_page: Math.min(limit, 50), _fields: listFields });
  return hydratePosts(posts.filter((post) => !post.categories.includes(VIP_CATEGORY_ID)));
}

export async function getStory(slug: string): Promise<Story | null> {
  const posts = await wpFetch<WpPost[]>("posts", { ...postParams, slug, per_page: 1, _fields: detailFields });
  const post = posts[0];
  if (!post || post.categories.includes(VIP_CATEGORY_ID)) return null;
  return (await hydratePosts([post]))[0] ?? null;
}

export async function getCategory(slug: string): Promise<Category | null> {
  if (slug === "vip") return null;
  const categories = await wpFetch<Category[]>("categories", { slug, per_page: 1 });
  const category = categories[0];
  return !category || category.id === VIP_CATEGORY_ID ? null : category;
}

export async function getCategoryStories(
  categoryId: number,
  page = 1,
  options: WpFetchOptions = {},
): Promise<CategoryStories> {
  const safePage = Math.max(1, page);
  const posts = await wpFetch<WpPost[]>("posts", {
    ...postParams,
    categories: categoryId,
    offset: (safePage - 1) * ARCHIVE_PAGE_SIZE,
    per_page: ARCHIVE_PAGE_SIZE + 1,
    _fields: listFields,
  }, options);
  const visible = posts.filter((post) => !post.categories.includes(VIP_CATEGORY_ID));
  return {
    stories: await hydratePosts(visible.slice(0, ARCHIVE_PAGE_SIZE)),
    hasNext: visible.length > ARCHIVE_PAGE_SIZE,
  };
}
