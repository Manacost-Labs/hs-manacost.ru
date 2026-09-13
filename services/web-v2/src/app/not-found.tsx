import Link from "next/link";

export default function NotFound() {
  return <div className="shell state-page"><span>404 / LOST IN THE TAVERN</span><h1>Такого материала здесь нет</h1><p>Возможно, он ещё не перенесён в экспериментальную версию.</p><Link href="/">Вернуться на главную</Link></div>;
}
