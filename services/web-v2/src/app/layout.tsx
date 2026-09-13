import type { Metadata } from "next";
import { SiteFooter } from "@/components/site-footer";
import { SiteHeader } from "@/components/site-header";
import "./globals.css";

export const metadata: Metadata = {
  metadataBase: new URL("https://v2.hs-manacost.ru"),
  title: { default: "Манакост V2 — Hearthstone медиа", template: "%s — Манакост V2" },
  description: "Тестовая версия Манакоста: новости, гайды и аналитика Hearthstone.",
  robots: { index: false, follow: false, nocache: true },
};

export default function RootLayout({ children }: Readonly<{ children: React.ReactNode }>) {
  return (
    <html lang="ru">
      <body>
        <a className="skip-link" href="#content">К содержанию</a>
        <SiteHeader />
        <main id="content">{children}</main>
        <SiteFooter />
      </body>
    </html>
  );
}
