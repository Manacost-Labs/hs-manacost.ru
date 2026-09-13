import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import { StoryCard } from "@/components/story-card";
import { getCategory, getCategoryStories } from "@/lib/wp";

type Props = { params: Promise<{ slug: string }>; searchParams: Promise<{ page?: string }> };

export async function generateMetadata({ params }: Props): Promise<Metadata> {
  const category = await getCategory((await params).slug);
  return { title: category?.name ?? "Раздел", robots: { index: false, follow: false } };
}

export default async function CategoryPage({ params, searchParams }: Props) {
  const { slug } = await params;
  const page = Math.max(1, Number.parseInt((await searchParams).page ?? "1", 10) || 1);
  const category = await getCategory(slug);
  if (!category) notFound();
  const { stories, hasNext } = await getCategoryStories(category.id, page);
  if (page > 1 && stories.length === 0) notFound();

  return (
    <div className="shell page-stack">
      <header className="archive-header">
        <span className="eyebrow">Раздел / {String(page).padStart(2, "0")}</span>
        <h1>{category.name}</h1>
        {category.description && <p>{category.description.replace(/<[^>]+>/g, "")}</p>}
      </header>
      <div className="feed-list">
        {stories.map((story) => <StoryCard key={story.id} story={story} />)}
      </div>
      <nav className="pagination" aria-label="Навигация по страницам">
        {page > 1 && <Link href={`/category/${slug}?page=${page - 1}`}>← Новее</Link>}
        {hasNext && <Link href={`/category/${slug}?page=${page + 1}`}>Раньше →</Link>}
      </nav>
    </div>
  );
}
