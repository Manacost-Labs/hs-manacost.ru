export const dynamic = "force-dynamic";

export function GET() {
  return Response.json({ status: "ok", service: "hs-manacost-v2" }, { headers: { "Cache-Control": "no-store" } });
}
