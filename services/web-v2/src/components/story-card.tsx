import Link from "next/link";
import type { Story } from "@/lib/wp";

function StoryImage({ story, eager = false }: { story: Story; eager?: boolean }) {
  return story.image ? (
    // WordPress owns image dimensions and variants; the frame prevents layout shift.
    // eslint-disable-next-line @next/next/no-img-element
    <img src={story.image} alt={story.imageAlt} loading={eager ? "eager" : "lazy"} decoding="async" />
  ) : <div className="image-fallback" aria-hidden="true"><span>MC</span></div>;
}

export function StoryCard({ story, variant = "standard", eager = false }: { story: Story; variant?: "hero" | "standard" | "compact"; eager?: boolean }) {
  const category = story.categories[0];
  return (
    <article className={`story-card story-card--${variant}`}>
      <Link className="story-image" href={`/${story.slug}`} aria-label={story.title}>
        <StoryImage story={story} eager={eager} />
      </Link>
      <div className="story-copy">
        <div className="story-meta">
          {category && <Link href={`/category/${category.slug}`}>{category.name}</Link>}
          <time dateTime={story.date}>{new Intl.DateTimeFormat("ru-RU", { day: "numeric", month: "short" }).format(new Date(story.date))}</time>
        </div>
        <h2><Link href={`/${story.slug}`}>{story.title}</Link></h2>
        {variant !== "compact" && story.excerpt && <p>{story.excerpt}</p>}
      </div>
    </article>
  );
}
