"use client";

export default function ErrorPage({ reset }: { error: Error & { digest?: string }; reset: () => void }) {
  return (
    <div className="shell state-page" role="alert">
      <span>UPSTREAM / PAUSE</span>
      <h1>Не удалось загрузить материалы</h1>
      <p>WordPress временно не ответил. Уже открытые страницы продолжат работать из кэша.</p>
      <button type="button" onClick={reset}>Попробовать снова</button>
    </div>
  );
}
