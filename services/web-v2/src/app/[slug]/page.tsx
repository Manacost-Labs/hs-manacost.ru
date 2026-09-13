import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import { readingMinutes } from "@/lib/content";
import { getStory } from "@/lib/wp";

type Props = { params: Promise<{ slug: string }> };

export async function generateMetadata({ params }: Props): Promise<Metadata> {
  const story = await getStory((await params).slug);
  return {
    title: story?.title ?? "Материал",
    description: story?.excerpt,
    robots: { index: false, follow: false },
  };
}

export default async function ArticlePage({ params }: Props) {
  const story = await getStory((await params).slug);
  if (!story) notFound();
  const category = story.categories[0];

  return (
    <article className="article-shell shell">
      <header className="article-header">
        <div className="article-meta">
          {category && <Link href={`/category/${category.slug}`}>{category.name}</Link>}
          <time dateTime={story.date}>{new Intl.DateTimeFormat("ru-RU", { day: "numeric", month: "long", year: "numeric" }).format(new Date(story.date))}</time>
          <span>{readingMinutes(story.content)} мин чтения</span>
        </div>
        <h1>{story.title}</h1>
        {story.excerpt && <p>{story.excerpt}</p>}
      </header>
      {story.image && (
        <figure className="article-cover">
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img src={story.image} alt={story.imageAlt} loading="eager" decoding="async" />
        </figure>
      )}
      <div className="article-layout">
        <aside className="article-rail"><span>МАНА / {String(story.id).slice(-3)}</span><i /></aside>
        <div className="article-body" dangerouslySetInnerHTML={{ __html: story.content }} />
      </div>
      <footer className="article-end">
        <span>Конец материала</span>
        <a href={story.sourceUrl}>Открыть оригинал на hs-manacost.ru ↗</a>
      </footer>
    </article>
  );
}
