import Link from "next/link";

const nav = [
  ["Новости", "novosti"],
  ["Гайды", "gajdy-hearthstone"],
  ["Мета", "meta-game"],
  ["Поля сражений", "polya-srazhenij"],
  ["Аналитика", "analitika-hearthstone"],
];

export function SiteHeader() {
  return (
    <>
      <div className="preview-bar">
        <span className="pulse" aria-hidden="true" />
        V2 LAB · тестовая версия без авторизации
      </div>
      <header className="site-header">
        <div className="shell header-row">
          <Link className="brand" href="/" aria-label="Манакост V2 — на главную">
            <span className="brand-mark">M</span>
            <span><b>МАНАКОСТ</b><small>HEARTHSTONE MEDIA</small></span>
          </Link>
          <nav className="desktop-nav" aria-label="Основная навигация">
            {nav.map(([name, slug]) => <Link key={slug} href={`/category/${slug}`}>{name}</Link>)}
          </nav>
          <a className="legacy-link" href="https://hs-manacost.ru">Текущий сайт ↗</a>
        </div>
        <nav className="mobile-nav shell" aria-label="Разделы">
          {nav.map(([name, slug]) => <Link key={slug} href={`/category/${slug}`}>{name}</Link>)}
        </nav>
      </header>
    </>
  );
}
