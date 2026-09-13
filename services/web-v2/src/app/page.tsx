import Link from "next/link";
import { StoryCard } from "@/components/story-card";
import { getLatestStories } from "@/lib/wp";

export const dynamic = "force-dynamic";

export default async function HomePage() {
  const stories = await getLatestStories(13);
  const [hero, ...rest] = stories;

  return (
    <div className="shell page-stack">
      <section className="lead-grid" aria-label="Главные материалы">
        {hero && <StoryCard story={hero} variant="hero" eager />}
        <div className="lead-list">
          <div className="section-kicker"><span>Сейчас читают</span><b>01</b></div>
          {rest.slice(0, 4).map((story) => <StoryCard key={story.id} story={story} variant="compact" />)}
        </div>
      </section>

      <section>
        <div className="section-heading">
          <div><span className="eyebrow">Редакционная лента</span><h1>Свежее на Манакосте</h1></div>
          <Link href="/category/novosti">Все новости →</Link>
        </div>
        <div className="card-grid">
          {rest.slice(4).map((story) => <StoryCard key={story.id} story={story} />)}
        </div>
      </section>

      <aside className="lab-note">
        <span>V2 / LAB NOTE</span>
        <h2>Сайт меняется прямо здесь</h2>
        <p>Это независимая тестовая версия. Мы постепенно добавим новые шаблоны, интерактивные форматы и инструменты для чтения — основной сайт продолжает работать как раньше.</p>
      </aside>
    </div>
  );
}
